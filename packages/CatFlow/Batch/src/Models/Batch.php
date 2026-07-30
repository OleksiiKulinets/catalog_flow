<?php

namespace CatFlow\Batch\Models;

use CatFlow\File\Models\Dataset;
use CatFlow\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    // promptTemplate() relation lands once the Prompt package has its model.

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
        ];
    }
}
