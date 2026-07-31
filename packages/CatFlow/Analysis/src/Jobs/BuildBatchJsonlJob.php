<?php

namespace CatFlow\Analysis\Jobs;

use CatFlow\Analysis\Jsonl\BatchJsonlBuilder;
use CatFlow\Batch\Models\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BuildBatchJsonlJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly Batch $batch)
    {
    }

    public function handle(BatchJsonlBuilder $builder): void
    {
        $path = $builder->build($this->batch);

        $this->batch->update([
            'input_jsonl_path' => $path,
            'request_total' => $this->batch->dataset->rows_count,
        ]);
    }
}
