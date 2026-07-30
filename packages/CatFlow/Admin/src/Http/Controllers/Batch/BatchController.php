<?php

namespace CatFlow\Admin\Http\Controllers\Batch;

use CatFlow\Admin\Http\Controllers\Controller;
use CatFlow\Admin\Http\Requests\Batch\StoreBatchRequest;
use CatFlow\Batch\Models\Batch;
use CatFlow\File\Services\DatasetStorageService;
use CatFlow\File\Services\GoogleSheets\GoogleSheetsImportException;
use CatFlow\File\Services\GoogleSheets\GoogleSheetsImportService;
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
     */
    public function show(Request $request, Batch $batch): View
    {
        abort_unless($batch->user_id === $request->user()->id, 404);

        $batch->loadMissing('dataset');

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

        Batch::create([
            'user_id' => $user->id,
            'dataset_id' => $dataset->id,
            'provider' => 'openai',
            'model' => $validated['model'],
            'output_format' => $validated['output_format'],
            'prompt' => $validated['prompt'],
            'status' => 'draft',
        ]);

        return Redirect::route('batches.create')
            ->with('status', 'batch-created')
            ->with('created_dataset', [
                'name' => $dataset->name,
                'rows_count' => $dataset->rows_count,
            ]);
    }
}
