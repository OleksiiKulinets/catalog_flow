<?php

namespace CatFlow\Admin\Http\Controllers\Batch;

use CatFlow\Admin\Http\Controllers\Controller;
use CatFlow\Admin\Http\Requests\Batch\ConfirmDatasetSchemaRequest;
use CatFlow\Admin\Http\Requests\Batch\StoreBatchRequest;
use CatFlow\Analysis\Jobs\AnalyzeDatasetStructureJob;
use CatFlow\Analysis\Jobs\BuildBatchJsonlJob;
use CatFlow\Analysis\Models\DatasetColumn;
use CatFlow\Analysis\Models\DatasetSchema;
use CatFlow\Analysis\Services\ProductPreviewBuilder;
use CatFlow\Batch\Jobs\PollBatchStatusJob;
use CatFlow\Batch\Models\Batch;
use CatFlow\Batch\Services\BatchStatusPoller;
use CatFlow\Batch\Services\BatchSubmissionService;
use CatFlow\File\Services\DatasetStorageService;
use CatFlow\File\Services\GoogleSheets\GoogleSheetsImportException;
use CatFlow\File\Services\GoogleSheets\GoogleSheetsImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class BatchController extends Controller
{
    /**
     * Display a listing of the user's batches.
     */
    public function index(Request $request): View
    {
        $query = Batch::query()
            ->with('dataset')
            ->where('user_id', $request->user()->id);

        if ($search = trim((string) $request->get('search'))) {
            $query->whereHas('dataset', fn ($q) => $q->where('name', 'like', "%{$search}%"));
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($model = $request->get('model')) {
            $query->where('model', $model);
        }

        if ($date = $request->get('date')) {
            $since = match ($date) {
                'today' => now()->startOfDay(),
                'week' => now()->subDays(7),
                'month' => now()->subDays(30),
                default => null,
            };

            if ($since) {
                $query->where('created_at', '>=', $since);
            }
        }

        $sort = $request->get('sort', 'newest');
        $query->orderBy('created_at', $sort === 'oldest' ? 'asc' : 'desc');

        $batches = $query->paginate(5)->withQueryString();

        $allModels = Batch::query()
            ->where('user_id', $request->user()->id)
            ->distinct()
            ->orderBy('model')
            ->pluck('model');

        return view('batches.index', [
            'batches' => $batches->through(fn (Batch $batch) => $batch->toDisplayArray()),
            'allModels' => $allModels,
            'filters' => $request->only(['search', 'status', 'model', 'date', 'sort']),
        ]);
    }

    /**
     * Display the batch creation form.
     */
    public function create(): View
    {
        return view('batches.create');
    }

    /**
     * Display a single batch's details.
     *
     * Also does a throttled status check (see BatchStatusPoller::pollIfStale)
     * on every visit — the live JS polling on this page only keeps things
     * fresh while someone has it open with a working browser, and the
     * background PollBatchStatusJob only helps if a queue worker is
     * actually running. Neither is guaranteed, so a plain page load/refresh
     * needs to be able to catch things up on its own too.
     */
    public function show(Request $request, Batch $batch, BatchStatusPoller $poller): View
    {
        abort_unless($batch->user_id === $request->user()->id, 404);

        $batch->loadMissing('dataset');
        $poller->pollIfStale($batch);

        return view('batches.show', [
            'batch' => $batch->toDisplayArray(),
        ]);
    }

    /**
     * Handle an incoming batch creation request.
     */
    public function store(
        StoreBatchRequest $request,
        DatasetStorageService $fileStorage,
        GoogleSheetsImportService $sheetsImport
    ): RedirectResponse {
        $validated = $request->validated();
        $user = $request->user();

        if ($request->hasFile('dataset')) {
            $dataset = $fileStorage->storeUploadedFile($user, $request->file('dataset'));
        } else {
            try {
                $dataset = $sheetsImport->import($user, $validated['google_sheet_url']);
            } catch (GoogleSheetsImportException $e) {
                return Redirect::route('batches.create')
                    ->withInput()
                    ->with('status', 'google-sheets-error')
                    ->with('google_sheets_error_reason', $e->reason);
            }
        }

        $batch = Batch::create([
            'user_id' => $user->id,
            'dataset_id' => $dataset->id,
            'provider' => 'openai',
            'model' => $validated['model'],
            'output_format' => $validated['output_format'],
            'prompt' => $validated['prompt'],
            'status' => 'draft',
        ]);

        return Redirect::route('batches.analyze', $batch)
            ->with('created_dataset', [
                'name' => $dataset->name,
                'rows_count' => $dataset->rows_count,
            ]);
    }

    /**
     * Run (or show the result of) AI column-structure analysis for a
     * batch's dataset, then let the user preview/confirm the mapping.
     *
     * Analysis is only (re-)triggered when there's no schema yet or the
     * previous attempt failed — a confirmed/needs-review schema is shown
     * as-is on refresh, so revisiting this page never re-spends AI calls.
     */
    public function analyze(Request $request, Batch $batch, ProductPreviewBuilder $previewBuilder): View
    {
        abort_unless($batch->user_id === $request->user()->id, 404);

        $schema = DatasetSchema::where('dataset_id', $batch->dataset_id)->first();

        if (! $schema || in_array($schema->status, [DatasetSchema::STATUS_PENDING, DatasetSchema::STATUS_FAILED], true)) {
            try {
                AnalyzeDatasetStructureJob::dispatchSync($batch->dataset);
            } catch (\Throwable) {
                // SchemaAnalyzer already persisted the failure (and reported it) onto the schema,
                // regardless of what kind of exception it was — sampling/parsing errors included.
            }

            $schema = DatasetSchema::where('dataset_id', $batch->dataset_id)->first();
        }

        $preview = $schema && ($schema->isConfirmed() || $schema->needsReview())
            ? $previewBuilder->build($schema)
            : [];

        return view('batches.analyze', [
            'batch' => $batch,
            'schema' => $schema,
            'preview' => $preview,
            'canonicalFields' => DatasetColumn::allFields(),
            'dataTypes' => DatasetColumn::allDataTypes(),
            'confidenceThreshold' => (float) config('services.openai.analysis_confidence_threshold'),
        ]);
    }

    /**
     * Confirm (optionally edited) column mapping, build the batch's .jsonl
     * input file, then submit it to OpenAI's Batch API and start polling
     * for completion.
     */
    public function confirm(
        ConfirmDatasetSchemaRequest $request,
        Batch $batch,
        BatchSubmissionService $submission
    ): RedirectResponse {
        abort_unless($batch->user_id === $request->user()->id, 404);
        abort_unless($batch->status === 'draft', 404);

        $schema = DatasetSchema::where('dataset_id', $batch->dataset_id)->firstOrFail();

        foreach ($request->validated('columns') as $columnId => $edit) {
            $schema->columns()->whereKey($columnId)->update([
                'canonical_field' => $edit['canonical_field'],
                'data_type' => $edit['data_type'],
                'is_confirmed' => true,
            ]);
        }

        $schema->update([
            'status' => DatasetSchema::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);

        try {
            BuildBatchJsonlJob::dispatchSync($batch);

            // Even a "sync" dispatch of a ShouldQueue job round-trips through
            // serialization on the 'sync' queue connection, so the job worked
            // on its own copy of $batch — input_jsonl_path landed in the DB
            // but not on this in-memory instance until it's refreshed.
            $batch->refresh();

            $submission->submit($batch);
            PollBatchStatusJob::dispatch($batch)->delay(now()->addSeconds(5));
        } catch (\Throwable $e) {
            report($e);
            $batch->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
        }

        return Redirect::route('batches.show', $batch);
    }

    /**
     * Lightweight JSON status snapshot polled from the batch detail page.
     * Only performs a real OpenAI call when due — see
     * BatchStatusPoller::pollIfStale() — so frequent client-side polling
     * doesn't translate into hammering OpenAI's API.
     */
    public function status(Request $request, Batch $batch, BatchStatusPoller $poller): JsonResponse
    {
        abort_unless($batch->user_id === $request->user()->id, 404);

        $poller->pollIfStale($batch);

        return response()->json([
            'status' => $batch->status,
            'done' => $batch->request_completed,
            'total' => $batch->request_total,
            'eta_seconds' => $batch->estimatedSecondsRemaining(),
            'eta_human' => $batch->estimatedTimeRemainingHuman(),
        ]);
    }
}
