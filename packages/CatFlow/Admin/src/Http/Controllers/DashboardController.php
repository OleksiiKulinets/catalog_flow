<?php

namespace CatFlow\Admin\Http\Controllers;

use Carbon\Carbon;
use CatFlow\Batch\Models\Batch;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const ACTIVITY_WEEKS = 12;

    public function index(Request $request): View
    {
        $userId = $request->user()->id;
        $base = fn (): Builder => Batch::query()->where('user_id', $userId);

        return view('dashboard.index', [
            'stats' => [
                'projects' => $base()->count(),
                'running' => $base()->where('status', 'in_progress')->count(),
                'completedToday' => $base()->where('status', 'completed')->whereDate('finished_at', now())->count(),
                'failed' => $base()->where('status', 'failed')->count(),
            ],
            'aiActivityStats' => [
                'totalRequests' => $base()->sum('request_completed'),
                'projectsCreated' => $base()->count(),
                'batchesCompleted' => $base()->where('status', 'completed')->count(),
                'batchesFailed' => $base()->where('status', 'failed')->count(),
            ],
            'activityByDay' => $this->activityByDay($userId, self::ACTIVITY_WEEKS),
            'activityWeeks' => self::ACTIVITY_WEEKS,
            'activityFeed' => $base()->with('dataset')->latest()->take(5)->get()
                ->map(fn (Batch $batch) => $batch->toDisplayArray()),
            'runningBatches' => $base()->with('dataset')->where('status', 'in_progress')->latest()->take(3)->get()
                ->map(fn (Batch $batch) => $batch->toDisplayArray()),
            'recentBatches' => $base()->with('dataset')->latest()->take(5)->get()
                ->map(fn (Batch $batch) => $batch->toDisplayArray()),
        ]);
    }

    /**
     * One entry per day for the last $weeks weeks (Sun–Sat columns), each
     * with how many batches were created that day — the raw data behind the
     * GitHub-style activity heatmap.
     *
     * @return array<int, array{date: string, label: string, count: int}>
     */
    private function activityByDay(int $userId, int $weeks): array
    {
        $today = now()->startOfDay();
        $start = $today->copy()->subWeeks($weeks - 1)->startOfWeek(Carbon::SUNDAY);

        $counts = Batch::where('user_id', $userId)
            ->where('created_at', '>=', $start)
            ->get(['created_at'])
            ->countBy(fn (Batch $batch) => $batch->created_at->toDateString());

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
