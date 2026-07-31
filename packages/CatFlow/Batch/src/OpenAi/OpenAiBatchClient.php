<?php

namespace CatFlow\Batch\OpenAi;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class OpenAiBatchClient
{
    /**
     * The only completion window OpenAI's Batch API currently accepts —
     * not worth exposing as config until that changes.
     */
    private const COMPLETION_WINDOW = '24h';

    /**
     * Upload a .jsonl file to OpenAI's Files API for batch processing.
     */
    public function uploadFile(string $apiKey, string $absolutePath): string
    {
        // Passed as raw contents rather than a stream resource — avoids
        // manual fopen/fclose lifecycle management entirely, which is
        // fragile here since Laravel's HTTP client/test fakes may need to
        // re-read the request body after the request itself returns.
        $response = Http::withToken($apiKey)
            ->attach('file', file_get_contents($absolutePath), basename($absolutePath))
            ->post('https://api.openai.com/v1/files', ['purpose' => 'batch']);

        $this->guard($response);

        $id = $response->json('id');

        if (! is_string($id)) {
            throw BatchApiException::malformedResponse();
        }

        return $id;
    }

    /**
     * @return array<string, mixed>
     */
    public function createBatch(string $apiKey, string $inputFileId): array
    {
        $response = Http::withToken($apiKey)
            ->post('https://api.openai.com/v1/batches', [
                'input_file_id' => $inputFileId,
                'endpoint' => '/v1/chat/completions',
                'completion_window' => self::COMPLETION_WINDOW,
            ]);

        $this->guard($response);

        return $this->requireBatchObject($response);
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieveBatch(string $apiKey, string $providerJobId): array
    {
        $response = Http::withToken($apiKey)->get("https://api.openai.com/v1/batches/{$providerJobId}");

        $this->guard($response);

        return $this->requireBatchObject($response);
    }

    public function downloadFileContent(string $apiKey, string $fileId): string
    {
        $response = Http::withToken($apiKey)->get("https://api.openai.com/v1/files/{$fileId}/content");

        $this->guard($response);

        return $response->body();
    }

    /**
     * Map OpenAI's batch status vocabulary onto ours — every value already
     * matches except 'validating', which we surface as 'queued'.
     */
    public static function mapStatus(string $remoteStatus): string
    {
        return $remoteStatus === 'validating' ? 'queued' : $remoteStatus;
    }

    private function guard(Response $response): void
    {
        if ($response->status() === 401) {
            throw BatchApiException::invalidApiKey();
        }

        if ($response->status() === 429) {
            throw BatchApiException::rateLimited();
        }

        if ($response->failed()) {
            throw BatchApiException::requestFailed();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function requireJson(Response $response): array
    {
        $body = $response->json();

        if (! is_array($body)) {
            throw BatchApiException::malformedResponse();
        }

        return $body;
    }

    /**
     * Same as requireJson(), but also validates the two fields every
     * caller of createBatch()/retrieveBatch() depends on are present.
     *
     * @return array<string, mixed>
     */
    private function requireBatchObject(Response $response): array
    {
        $body = $this->requireJson($response);

        if (! isset($body['id'], $body['status']) || ! is_string($body['id']) || ! is_string($body['status'])) {
            throw BatchApiException::malformedResponse();
        }

        return $body;
    }
}
