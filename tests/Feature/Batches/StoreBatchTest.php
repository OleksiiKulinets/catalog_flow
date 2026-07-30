<?php

namespace Tests\Feature\Batches;

use CatFlow\Batch\Models\Batch;
use CatFlow\File\Models\Dataset;
use CatFlow\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StoreBatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploading_a_csv_file_creates_a_real_dataset_and_batch(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $file = UploadedFile::fake()->createWithContent(
            'products.csv',
            "name,price\nWidget,9.99\nGadget,19.99\n"
        );

        $response = $this->actingAs($user)->post('/batches', [
            'model' => 'gpt-4.1',
            'output_format' => 'csv',
            'prompt' => 'Translate the name column to Spanish.',
            'dataset' => $file,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('batches.create'));
        $this->assertSame('batch-created', session('status'));

        $this->assertDatabaseCount('datasets', 1);
        $dataset = Dataset::first();
        $this->assertSame($user->id, $dataset->user_id);
        $this->assertSame(Dataset::SOURCE_UPLOAD, $dataset->source_type);
        $this->assertSame('csv', $dataset->source_format);
        $this->assertSame('products.csv', $dataset->original_filename);
        $this->assertSame(2, $dataset->rows_count);
        $this->assertNull($dataset->external_url);
        Storage::disk('local')->assertExists($dataset->storage_path);

        $this->assertDatabaseCount('batches', 1);
        $batch = Batch::first();
        $this->assertSame($user->id, $batch->user_id);
        $this->assertSame($dataset->id, $batch->dataset_id);
        $this->assertSame('gpt-4.1', $batch->model);
        $this->assertSame('csv', $batch->output_format);
        $this->assertSame('draft', $batch->status);
        $this->assertSame($dataset->id, $batch->dataset->id);
    }

    public function test_uploading_a_file_alongside_an_empty_google_sheet_url_field_still_succeeds(): void
    {
        // Real browsers always submit both source-tabs' fields — the
        // inactive tab is merely hidden via x-show, not removed from the
        // DOM — so google_sheet_url arrives as an empty string rather than
        // being absent whenever the user is on the "Upload file" tab.
        Storage::fake('local');

        $user = User::factory()->create();
        $file = UploadedFile::fake()->createWithContent('products.csv', "name,price\nWidget,9.99\n");

        $response = $this->actingAs($user)->post('/batches', [
            'model' => 'gpt-4.1',
            'output_format' => 'csv',
            'prompt' => 'do something',
            'dataset' => $file,
            'google_sheet_url' => '',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('batches.create'));
        $this->assertSame('batch-created', session('status'));
        $this->assertDatabaseCount('batches', 1);
    }

    public function test_submitting_both_a_file_and_a_real_google_sheet_url_is_rejected(): void
    {
        // A leftover value in the inactive tab (e.g. the user pasted a URL,
        // then switched to Upload file and picked a file) must be rejected
        // rather than silently guessing which source to use.
        Storage::fake('local');

        $user = User::factory()->create();
        $file = UploadedFile::fake()->createWithContent('products.csv', "name,price\nWidget,9.99\n");

        $response = $this->actingAs($user)->post('/batches', [
            'model' => 'gpt-4.1',
            'output_format' => 'csv',
            'prompt' => 'do something',
            'dataset' => $file,
            'google_sheet_url' => 'https://docs.google.com/spreadsheets/d/abc123/edit',
        ]);

        $response->assertSessionHasErrors(['dataset', 'google_sheet_url']);
        $this->assertDatabaseCount('datasets', 0);
        $this->assertDatabaseCount('batches', 0);
    }

    public function test_submitting_neither_a_file_nor_a_google_sheet_url_is_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/batches', [
            'model' => 'gpt-4.1',
            'output_format' => 'csv',
            'prompt' => 'do something',
            'google_sheet_url' => '',
        ]);

        $response->assertSessionHasErrors(['dataset', 'google_sheet_url']);
        $this->assertDatabaseCount('batches', 0);
    }

    public function test_uploading_a_json_file_computes_rows_count(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $file = UploadedFile::fake()->createWithContent(
            'rows.json',
            json_encode([
                ['name' => 'Widget'],
                ['name' => 'Gadget'],
                ['name' => 'Gizmo'],
            ])
        );

        $this->actingAs($user)->post('/batches', [
            'model' => 'gpt-4o-mini',
            'output_format' => 'json',
            'prompt' => 'do something',
            'dataset' => $file,
        ]);

        $dataset = Dataset::first();
        $this->assertSame('json', $dataset->source_format);
        $this->assertSame(3, $dataset->rows_count);
    }

    public function test_submitting_a_public_google_sheet_url_creates_a_real_dataset_and_batch(): void
    {
        Storage::fake('local');
        Http::fake([
            'docs.google.com/*' => Http::response("name,price\nWidget,9.99\n", 200, ['Content-Type' => 'text/csv']),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/batches', [
            'model' => 'gpt-4.1',
            'output_format' => 'google_sheet',
            'prompt' => 'do something',
            'google_sheet_url' => 'https://docs.google.com/spreadsheets/d/abc123/edit#gid=0',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('batches.create'));
        $this->assertSame('batch-created', session('status'));

        $this->assertDatabaseCount('datasets', 1);
        $dataset = Dataset::first();
        $this->assertSame($user->id, $dataset->user_id);
        $this->assertSame(Dataset::SOURCE_GOOGLE_SHEET, $dataset->source_type);
        $this->assertSame('csv', $dataset->source_format);
        $this->assertSame('abc123', $dataset->spreadsheet_id);
        $this->assertSame('0', $dataset->sheet_gid);
        $this->assertSame('https://docs.google.com/spreadsheets/d/abc123/edit#gid=0', $dataset->external_url);
        $this->assertSame(1, $dataset->rows_count);
        Storage::disk('local')->assertExists($dataset->storage_path);

        $this->assertDatabaseCount('batches', 1);
        $batch = Batch::first();
        $this->assertSame('google_sheet', $batch->output_format);
        $this->assertSame($dataset->id, $batch->dataset_id);
    }

    public function test_submitting_an_invalid_google_sheet_url_shows_error_and_creates_nothing(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/batches', [
            'model' => 'gpt-4.1',
            'output_format' => 'csv',
            'prompt' => 'do something',
            'google_sheet_url' => 'https://docs.google.com/not-a-sheet-url',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('batches.create'));
        $this->assertSame('google-sheets-error', session('status'));
        $this->assertSame('invalid_url', session('google_sheets_error_reason'));

        $this->assertDatabaseCount('datasets', 0);
        $this->assertDatabaseCount('batches', 0);
    }

    public function test_submitting_a_private_google_sheet_url_shows_not_public_error(): void
    {
        Http::fake([
            'docs.google.com/*' => Http::response('<html>sign in</html>', 200, ['Content-Type' => 'text/html']),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/batches', [
            'model' => 'gpt-4.1',
            'output_format' => 'csv',
            'prompt' => 'do something',
            'google_sheet_url' => 'https://docs.google.com/spreadsheets/d/private123/edit',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('batches.create'));
        $this->assertSame('google-sheets-error', session('status'));
        $this->assertSame('not_public', session('google_sheets_error_reason'));

        $this->assertDatabaseCount('datasets', 0);
        $this->assertDatabaseCount('batches', 0);
    }
}
