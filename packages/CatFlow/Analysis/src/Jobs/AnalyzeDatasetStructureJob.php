<?php

namespace CatFlow\Analysis\Jobs;

use CatFlow\Analysis\Services\SchemaAnalyzer;
use CatFlow\File\Models\Dataset;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AnalyzeDatasetStructureJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly Dataset $dataset)
    {
    }

    public function handle(SchemaAnalyzer $analyzer): void
    {
        $analyzer->analyze($this->dataset);
    }
}
