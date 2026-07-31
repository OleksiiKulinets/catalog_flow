<?php

namespace CatFlow\Analysis\OpenAi;

use CatFlow\Analysis\Services\AnalysisPromptBuilder;
use CatFlow\User\Models\User;
use Illuminate\Support\Facades\Http;

class OpenAiAnalysisClient
{
    public function __construct(
        private readonly AnalysisPromptBuilder $prompts,
    ) {
    }

    /**
     * Ask OpenAI to map each dataset column onto the fixed canonical
     * product-field vocabulary, using structured outputs (json_schema,
     * strict mode) so the response never needs a lenient free-text parser.
     *
     * @param  array<int, string>  $columns
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{columns: array<int, array<string, mixed>>, overall_confidence: float}
     */
    public function analyzeColumns(User $user, array $columns, array $rows, string $model): array
    {
        $apiKey = $user->apiKey?->encrypted_key;

        if (! $apiKey) {
            throw AnalysisException::missingApiKey();
        }

        $payload = $this->prompts->build($columns, $rows);

        $response = Http::withToken($apiKey)
            ->timeout(30)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => $payload['messages'],
                'response_format' => $payload['response_format'],
            ]);

        if ($response->status() === 401) {
            throw AnalysisException::invalidApiKey();
        }

        if ($response->status() === 429) {
            throw AnalysisException::rateLimited();
        }

        if ($response->failed()) {
            throw AnalysisException::requestFailed();
        }

        return $this->parse($response->json());
    }

    /**
     * @param  array<string, mixed>|null  $body
     * @return array{columns: array<int, array<string, mixed>>, overall_confidence: float}
     */
    private function parse(?array $body): array
    {
        $content = $body['choices'][0]['message']['content'] ?? null;

        if (! is_string($content)) {
            throw AnalysisException::malformedResponse();
        }

        $decoded = json_decode($content, true);

        if (! is_array($decoded)
            || ! isset($decoded['columns'], $decoded['overall_confidence'])
            || ! is_array($decoded['columns'])) {
            throw AnalysisException::malformedResponse();
        }

        return [
            'columns' => $decoded['columns'],
            'overall_confidence' => (float) $decoded['overall_confidence'],
        ];
    }
}
