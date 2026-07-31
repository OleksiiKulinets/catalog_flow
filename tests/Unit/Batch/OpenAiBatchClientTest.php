<?php

namespace Tests\Unit\Batch;

use CatFlow\Batch\OpenAi\BatchApiException;
use CatFlow\Batch\OpenAi\OpenAiBatchClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenAiBatchClientTest extends TestCase
{
    private const API_KEY = 'sk-test-0123456789';

    private function client(): OpenAiBatchClient
    {
        return new OpenAiBatchClient();
    }

    private function tempJsonlPath(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'catflow');
        file_put_contents($path, "{\"custom_id\":\"row-0\"}\n");

        return $path;
    }

    public function test_upload_file_returns_the_file_id(): void
    {
        Http::fake(['api.openai.com/v1/files' => Http::response(['id' => 'file-abc123'], 200)]);

        $id = $this->client()->uploadFile(self::API_KEY, $this->tempJsonlPath());

        $this->assertSame('file-abc123', $id);
        // Multipart bodies aren't exposed via $request['...'] the way JSON/form
        // bodies are — assert against the raw encoded body instead.
        Http::assertSent(fn ($request) => str_contains($request->body(), 'name="purpose"')
            && str_contains($request->body(), 'batch'));
    }

    public function test_upload_file_throws_malformed_response_when_id_is_missing(): void
    {
        Http::fake(['api.openai.com/v1/files' => Http::response(['ok' => true], 200)]);

        $this->expectException(BatchApiException::class);

        $this->client()->uploadFile(self::API_KEY, $this->tempJsonlPath());
    }

    public function test_create_batch_returns_the_raw_batch_object(): void
    {
        Http::fake(['api.openai.com/v1/batches' => Http::response(['id' => 'batch-abc', 'status' => 'validating'], 200)]);

        $remote = $this->client()->createBatch(self::API_KEY, 'file-abc123');

        $this->assertSame('batch-abc', $remote['id']);
        $this->assertSame('validating', $remote['status']);
        Http::assertSent(fn ($request) => $request['input_file_id'] === 'file-abc123'
            && $request['endpoint'] === '/v1/chat/completions'
            && $request['completion_window'] === '24h');
    }

    public function test_create_batch_throws_malformed_response_when_status_is_missing(): void
    {
        Http::fake(['api.openai.com/v1/batches' => Http::response(['id' => 'batch-abc'], 200)]);

        $this->expectException(BatchApiException::class);

        $this->client()->createBatch(self::API_KEY, 'file-abc123');
    }

    public function test_retrieve_batch_returns_the_raw_batch_object(): void
    {
        Http::fake(['api.openai.com/v1/batches/batch-abc' => Http::response([
            'id' => 'batch-abc',
            'status' => 'completed',
            'request_counts' => ['total' => 2, 'completed' => 2, 'failed' => 0],
        ], 200)]);

        $remote = $this->client()->retrieveBatch(self::API_KEY, 'batch-abc');

        $this->assertSame('completed', $remote['status']);
        $this->assertSame(2, $remote['request_counts']['total']);
    }

    public function test_download_file_content_returns_the_raw_body(): void
    {
        Http::fake(['api.openai.com/v1/files/file-out/content' => Http::response("{\"custom_id\":\"row-0\"}\n", 200)]);

        $content = $this->client()->downloadFileContent(self::API_KEY, 'file-out');

        $this->assertSame("{\"custom_id\":\"row-0\"}\n", $content);
    }

    public function test_throws_invalid_api_key_on_a_401_response(): void
    {
        Http::fake(['api.openai.com/*' => Http::response([], 401)]);

        try {
            $this->client()->retrieveBatch(self::API_KEY, 'batch-abc');
            $this->fail('Expected BatchApiException to be thrown.');
        } catch (BatchApiException $e) {
            $this->assertSame('invalid_api_key', $e->reason);
        }
    }

    public function test_throws_rate_limited_on_a_429_response(): void
    {
        Http::fake(['api.openai.com/*' => Http::response([], 429)]);

        try {
            $this->client()->retrieveBatch(self::API_KEY, 'batch-abc');
            $this->fail('Expected BatchApiException to be thrown.');
        } catch (BatchApiException $e) {
            $this->assertSame('rate_limited', $e->reason);
        }
    }

    public function test_throws_request_failed_on_a_generic_failure(): void
    {
        Http::fake(['api.openai.com/*' => Http::response([], 500)]);

        try {
            $this->client()->retrieveBatch(self::API_KEY, 'batch-abc');
            $this->fail('Expected BatchApiException to be thrown.');
        } catch (BatchApiException $e) {
            $this->assertSame('request_failed', $e->reason);
        }
    }

    public function test_map_status_translates_validating_to_queued_and_passes_through_everything_else(): void
    {
        $this->assertSame('queued', OpenAiBatchClient::mapStatus('validating'));
        $this->assertSame('in_progress', OpenAiBatchClient::mapStatus('in_progress'));
        $this->assertSame('completed', OpenAiBatchClient::mapStatus('completed'));
        $this->assertSame('failed', OpenAiBatchClient::mapStatus('failed'));
    }
}
