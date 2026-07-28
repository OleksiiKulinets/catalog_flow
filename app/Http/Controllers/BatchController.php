<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class BatchController extends Controller
{
    /**
     * Display a listing of the user's batches.
     */
    public function index(Request $request): View
    {
        $batches = collect($this->mockBatches());

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
            $today = Carbon::parse('2026-07-28')->startOfDay();
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
            'allModels' => collect($this->mockBatches())->pluck('model')->unique()->sort()->values(),
            'filters' => $request->only(['search', 'status', 'model', 'date', 'sort']),
        ]);
    }

    /**
     * Display a single batch's details.
     */
    public function show(int $batch): View
    {
        $batches = $this->mockBatches();

        return view('batches.show', [
            'batch' => $batches[$batch] ?? $batches[0],
        ]);
    }

    /**
     * Temporary mock dataset used until batches are backed by real jobs.
     *
     * @return array<int, array<string, mixed>>
     */
    public function mockBatches(): array
    {
        return [
            ['id' => 0, 'name' => 'products_catalog.xlsx', 'model' => 'GPT-4.1', 'time' => '2m ago', 'date' => '2026-07-28', 'status' => 'in_progress', 'done' => 42, 'total' => 120],
            ['id' => 1, 'name' => 'inventory_q3.csv', 'model' => 'GPT-4o mini', 'time' => '1h ago', 'date' => '2026-07-28', 'status' => 'completed', 'done' => 300, 'total' => 300],
            ['id' => 2, 'name' => 'customer_feedback.json', 'model' => 'o3-mini', 'time' => '3h ago', 'date' => '2026-07-28', 'status' => 'queued', 'done' => 0, 'total' => 85],
            ['id' => 3, 'name' => 'pricing_sheet.xlsx', 'model' => 'GPT-4.1', 'time' => 'Yesterday', 'date' => '2026-07-27', 'status' => 'failed', 'done' => 12, 'total' => 60],
            ['id' => 4, 'name' => 'supplier_list.csv', 'model' => 'GPT-4o', 'time' => '2 days ago', 'date' => '2026-07-26', 'status' => 'completed', 'done' => 210, 'total' => 210],
            ['id' => 5, 'name' => 'warehouse_stock.json', 'model' => 'GPT-4.1 mini', 'time' => '3 days ago', 'date' => '2026-07-25', 'status' => 'completed', 'done' => 95, 'total' => 95],
            ['id' => 6, 'name' => 'returns_report.xlsx', 'model' => 'o3', 'time' => '4 days ago', 'date' => '2026-07-24', 'status' => 'cancelled', 'done' => 8, 'total' => 150],
            ['id' => 7, 'name' => 'employee_directory.csv', 'model' => 'GPT-4o mini', 'time' => '5 days ago', 'date' => '2026-07-23', 'status' => 'completed', 'done' => 40, 'total' => 40],
        ];
    }
}
