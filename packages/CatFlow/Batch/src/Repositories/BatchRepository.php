<?php

namespace CatFlow\Batch\Repositories;

use Carbon\Carbon;

class BatchRepository
{
    /**
     * Temporary mock dataset used until batches are backed by real jobs.
     *
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
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

    /**
     * Fixed reference date the mock dataset is built around, shared by every
     * "today/this week/this month"-style calculation so they all agree.
     */
    public function today(): Carbon
    {
        return Carbon::parse('2026-07-28')->startOfDay();
    }

    /**
     * Most recent batches first, limited to $limit.
     *
     * @return array<int, array<string, mixed>>
     */
    public function recent(int $limit = 5): array
    {
        return collect($this->all())
            ->sortByDesc('date')
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * Batches currently being processed, most recent first, limited to $limit.
     *
     * @return array<int, array<string, mixed>>
     */
    public function running(int $limit = 3): array
    {
        return collect($this->all())
            ->where('status', 'in_progress')
            ->sortByDesc('date')
            ->take($limit)
            ->values()
            ->all();
    }

    public function count(): int
    {
        return count($this->all());
    }

    public function countByStatus(string $status): int
    {
        return collect($this->all())->where('status', $status)->count();
    }

    /**
     * Total individual rows processed across every batch — the closest proxy
     * we have to "AI requests made" until real per-request logging exists.
     */
    public function totalAiRequests(): int
    {
        return collect($this->all())->sum('done');
    }

    public function completedToday(): int
    {
        $today = $this->today();

        return collect($this->all())
            ->where('status', 'completed')
            ->filter(fn (array $batch) => Carbon::parse($batch['date'])->isSameDay($today))
            ->count();
    }

    /**
     * One entry per day for the last $weeks weeks (Sun–Sat columns), each with
     * how many batches were active that day — the raw data behind the
     * GitHub-style activity heatmap.
     *
     * @return array<int, array{date: string, count: int}>
     */
    public function activityByDay(int $weeks = 12): array
    {
        $today = $this->today();
        $start = $today->copy()->subWeeks($weeks - 1)->startOfWeek(Carbon::SUNDAY);

        $counts = collect($this->all())
            ->countBy('date');

        $days = [];

        for ($date = $start->copy(); $date->lte($today); $date->addDay()) {
            $key = $date->format('Y-m-d');

            $days[] = [
                'date' => $key,
                'label' => $date->toFormattedDateString(),
                'count' => $counts->get($key, 0),
            ];
        }

        return $days;
    }
}
