<?php

namespace Tests\Feature\Dashboard;

use CatFlow\Batch\Models\Batch;
use CatFlow\File\Models\Dataset;
use CatFlow\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function makeBatch(User $user, string $status = 'draft'): Batch
    {
        $dataset = Dataset::create([
            'user_id' => $user->id,
            'name' => 'test.csv',
            'source_type' => Dataset::SOURCE_UPLOAD,
            'source_format' => 'csv',
            'storage_path' => 'datasets/'.$user->id.'/test.csv',
            'rows_count' => 10,
        ]);

        return Batch::create([
            'user_id' => $user->id,
            'dataset_id' => $dataset->id,
            'provider' => 'openai',
            'model' => 'gpt-4.1',
            'output_format' => 'csv',
            'prompt' => 'do something',
            'status' => $status,
        ]);
    }

    public function test_dashboard_reflects_the_authenticated_users_real_batches(): void
    {
        $user = User::factory()->create();
        $this->makeBatch($user, 'draft');
        $this->makeBatch($user, 'failed');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertViewHas('stats', fn (array $stats) => $stats['projects'] === 2 && $stats['failed'] === 1);
    }

    public function test_dashboard_does_not_leak_other_users_batches(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->makeBatch($otherUser, 'completed');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertViewHas('stats', fn (array $stats) => $stats['projects'] === 0);
        $response->assertViewHas('recentBatches', fn ($batches) => $batches->isEmpty());
    }
}
