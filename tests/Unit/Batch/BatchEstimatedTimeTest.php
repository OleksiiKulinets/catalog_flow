<?php

namespace Tests\Unit\Batch;

use CatFlow\Batch\Models\Batch;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BatchEstimatedTimeTest extends TestCase
{
    private function batch(array $attributes): Batch
    {
        return new Batch(array_merge([
            'status' => 'in_progress',
            'request_total' => 0,
            'request_completed' => 0,
            'started_at' => null,
        ], $attributes));
    }

    public function test_null_without_a_started_at(): void
    {
        $batch = $this->batch(['request_total' => 100, 'request_completed' => 10]);

        $this->assertNull($batch->estimatedSecondsRemaining());
        $this->assertNull($batch->estimatedTimeRemainingHuman());
    }

    public function test_null_when_nothing_has_completed_yet(): void
    {
        $batch = $this->batch([
            'started_at' => now()->subMinutes(5),
            'request_total' => 100,
            'request_completed' => 0,
        ]);

        $this->assertNull($batch->estimatedSecondsRemaining());
    }

    #[DataProvider('terminalStatusProvider')]
    public function test_null_once_terminal_regardless_of_counts(string $status): void
    {
        $batch = $this->batch([
            'status' => $status,
            'started_at' => now()->subMinutes(5),
            'request_total' => 100,
            'request_completed' => 50,
        ]);

        $this->assertNull($batch->estimatedSecondsRemaining());
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function terminalStatusProvider(): array
    {
        return [['completed'], ['failed'], ['expired'], ['cancelled']];
    }

    public function test_extrapolates_from_elapsed_time_and_completed_count(): void
    {
        // 10 completed in 100s => 0.1/s; 90 remaining => 900s.
        $batch = $this->batch([
            'started_at' => now()->subSeconds(100),
            'request_total' => 100,
            'request_completed' => 10,
        ]);

        $eta = $batch->estimatedSecondsRemaining();

        // Allow a couple of seconds of slack for the test's own execution time.
        $this->assertNotNull($eta);
        $this->assertEqualsWithDelta(900, $eta, 30);
        $this->assertIsString($batch->estimatedTimeRemainingHuman());
    }

    public function test_zero_when_everything_is_already_completed_but_not_yet_marked_terminal(): void
    {
        $batch = $this->batch([
            'started_at' => now()->subSeconds(100),
            'request_total' => 10,
            'request_completed' => 10,
        ]);

        $this->assertSame(0, $batch->estimatedSecondsRemaining());
    }
}
