<?php

namespace CatFlow\Admin\Http\Controllers;

use Carbon\Carbon;
use CatFlow\Batch\Models\Batch;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    // 13 weeks = 91 days, the closest whole-week span to "last 90 days" —
    // the heatmap renders in full Sun–Sat columns, so it can't be exactly 90.
    private const ACTIVITY_WEEKS = 13;

    public function index(Request $request): View
    {
        $userId = $request->user()->id;
        $base = fn (): Builder => Batch::query()->where('user_id', $userId);

        $batchesCompleted = $base()->where('status', 'completed')->count();
        $batchesFailed = $base()->where('status', 'failed')->count();

        return view('dashboard.index', [
            'stats' => [
                'projects' => $base()->count(),
                'running' => $base()->where('status', 'in_progress')->count(),
                'completedToday' => $base()->where('status', 'completed')->whereDate('finished_at', now())->count(),
                'failed' => $base()->where('status', 'failed')->count(),
            ],
            'activityByDay' => $this->activityByDay($userId, self::ACTIVITY_WEEKS),
            'aiActivityStats' => [
                'totalRequests' => $base()->sum('request_completed'),
                'projectsCreated' => $base()->count(),
                'batchesCompleted' => $batchesCompleted,
                'batchesFailed' => $batchesFailed,
                // Guarded against a fresh account with zero of either.
                'successRate' => (int) round($batchesCompleted / max(1, $batchesCompleted + $batchesFailed) * 100),
            ],
            // Every dashboard list is capped at the same small count and
            // paired with a "View all" link rather than growing taller —
            // the whole dashboard is meant to fit one screen as a control
            // center, not read like a paginated list page.
            'runningBatches' => $base()->with('dataset')->where('status', 'in_progress')->latest()->take(5)->get()
                ->map(fn (Batch $batch) => $batch->toDisplayArray()),
            'failedBatches' => $base()->with('dataset')->where('status', 'failed')->latest()->take(5)->get()
                ->map(fn (Batch $batch) => $batch->toDisplayArray()),
            // Replaces what used to be two separate panels ("Recent
            // Projects" and "Activity Feed") showing the exact same
            // latest()->take(5) query with two different visual styles —
            // merged into one list, one job.
            'recentActivity' => $base()->with('dataset')->latest()->take(5)->get()
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
