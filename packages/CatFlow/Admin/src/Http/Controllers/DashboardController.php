<?php

namespace CatFlow\Admin\Http\Controllers;

use CatFlow\Batch\Repositories\BatchRepository;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const ACTIVITY_WEEKS = 12;

    public function __construct(private readonly BatchRepository $batches)
    {
    }

    public function index(): View
    {
        return view('dashboard.index', [
            'stats' => [
                'projects' => $this->batches->count(),
                'running' => $this->batches->countByStatus('in_progress'),
                'completedToday' => $this->batches->completedToday(),
                'failed' => $this->batches->countByStatus('failed'),
            ],
            'aiActivityStats' => [
                'totalRequests' => $this->batches->totalAiRequests(),
                'projectsCreated' => $this->batches->count(),
                'batchesCompleted' => $this->batches->countByStatus('completed'),
                'batchesFailed' => $this->batches->countByStatus('failed'),
            ],
            'activityByDay' => $this->batches->activityByDay(self::ACTIVITY_WEEKS),
            'activityWeeks' => self::ACTIVITY_WEEKS,
            'activityFeed' => $this->batches->recent(5),
            'runningBatches' => $this->batches->running(3),
            'recentBatches' => $this->batches->recent(5),
        ]);
    }
}
