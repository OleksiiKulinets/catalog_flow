<?php

namespace CatFlow\Batch\Models;

use Carbon\CarbonInterval;
use CatFlow\File\Models\Dataset;
use CatFlow\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Batch extends Model
{
    protected $fillable = [
        'user_id',
        'dataset_id',
        'prompt_template_id',
        'provider',
        'model',
        'output_format',
        'prompt',
        'status',
        'error_message',
        'provider_job_id',
        'input_file_id',
        'input_jsonl_path',
        'output_file_id',
        'error_file_id',
        'request_total',
        'request_completed',
        'request_failed',
        'started_at',
        'finished_at',
        'last_polled_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'last_polled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(Dataset::class);
    }

    public function outputs(): HasMany
    {
        return $this->hasMany(Output::class);
    }

    // promptTemplate() relation lands once the Prompt package has its model.

    /**
     * Simple linear extrapolation from elapsed time vs. requests completed
     * so far — no historical-snapshot smoothing, just "at this rate, how
     * long for the rest." Null until there's actually a rate to measure
     * (nothing completed yet) or once the batch is done either way.
     */
    public function estimatedSecondsRemaining(): ?int
    {
        if (in_array($this->status, ['completed', 'failed', 'expired', 'cancelled'], true)) {
            return null;
        }

        if (! $this->started_at || $this->request_completed <= 0) {
            return null;
        }

        $elapsedSeconds = max(1, $this->started_at->diffInSeconds(now()));
        $rate = $this->request_completed / $elapsedSeconds;

        if ($rate <= 0) {
            return null;
        }

        $remaining = max(0, $this->request_total - $this->request_completed);

        return (int) round($remaining / $rate);
    }

    /**
     * Human-readable form of estimatedSecondsRemaining(), e.g. "~5 minutes"
     * — one canonical formatting spot shared by the status JSON endpoint
     * and the initial (pre-JS) render of the batch detail page.
     */
    public function estimatedTimeRemainingHuman(): ?string
    {
        $seconds = $this->estimatedSecondsRemaining();

        if ($seconds === null) {
            return null;
        }

        return CarbonInterval::seconds($seconds)->cascade()->locale(app()->getLocale())->forHumans(['short' => true]);
    }

    /**
     * Shape this batch into the flat array structure the batches/dashboard
     * views expect. Assumes 'dataset' is already eager-loaded — call sites
     * are expected to have queried with with('dataset') to avoid N+1s.
     * 'done'/'total' stay 0/0 until the OpenAI submission + polling stage
     * starts populating request_completed/request_total.
     *
     * @return array<string, mixed>
     */
    public function toDisplayArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->dataset->name,
            'model' => $this->model,
            'prompt' => $this->prompt,
            'time' => $this->created_at->diffForHumans(),
            'date' => $this->created_at->toDateString(),
            'status' => $this->status,
            'done' => $this->request_completed,
            'total' => $this->request_total,
            'eta_seconds' => $this->estimatedSecondsRemaining(),
            'eta_human' => $this->estimatedTimeRemainingHuman(),
        ];
    }
}
