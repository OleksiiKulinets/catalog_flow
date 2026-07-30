<?php

namespace CatFlow\Admin\Http\Controllers\Batch;

use CatFlow\Admin\Http\Controllers\Controller;
use CatFlow\Batch\Repositories\BatchRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class BatchController extends Controller
{
    public function __construct(private readonly BatchRepository $batches)
    {
    }

    /**
     * Display a listing of the user's batches.
     */
    public function index(Request $request): View
    {
        $batches = collect($this->batches->all());

        if ($search = trim((string) $request->get('search'))) {
            $batches = $batches->filter(
                fn (array $batch) => str_contains(strtolower($batch['name']), strtolower($search))
            );
        }

        if ($status = $request->get('status')) {
            $batches = $batches->where('status', $status);
        }

        if ($model = $request->get('model')) {
            $batches = $batches->where('model', $model);
        }

        if ($date = $request->get('date')) {
            $today = $this->batches->today();
            $batches = $batches->filter(function (array $batch) use ($date, $today) {
                $batchDate = Carbon::parse($batch['date']);

                return match ($date) {
                    'today' => $batchDate->isSameDay($today),
                    'week' => $batchDate->greaterThanOrEqualTo($today->copy()->subDays(7)),
                    'month' => $batchDate->greaterThanOrEqualTo($today->copy()->subDays(30)),
                    default => true,
                };
            });
        }

        $sort = $request->get('sort', 'newest');
        $batches = $sort === 'oldest'
            ? $batches->sortBy('date')
            : $batches->sortByDesc('date');

        $items = $batches->values();
        $perPage = 5;
        $page = LengthAwarePaginator::resolveCurrentPage();

        $paginated = new LengthAwarePaginator(
            $items->forPage($page, $perPage),
            $items->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('batches.index', [
            'batches' => $paginated,
            'allModels' => collect($this->batches->all())->pluck('model')->unique()->sort()->values(),
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
    public function show(int $batch): View
    {
        $batches = $this->batches->all();

        return view('batches.show', [
            'batch' => $batches[$batch] ?? $batches[0],
        ]);
    }
}
