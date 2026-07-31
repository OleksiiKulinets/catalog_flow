<?php

namespace Tests\Unit\Analysis;

use CatFlow\Analysis\OpenAi\AnalysisException;
use CatFlow\Analysis\OpenAi\OpenAiAnalysisClient;
use CatFlow\Analysis\Services\AnalysisPromptBuilder;
use CatFlow\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenAiAnalysisClientTest extends TestCase
{
    use RefreshDatabase;

    private function userWithApiKey(): User
    {
        $user = User::factory()->create();

        $user->apiKey()->create([
            'provider' => 'openai',
            'encrypted_key' => 'sk-test-0123456789',
            'last_four' => '6789',
        ]);

        return $user;
    }

    private function client(): OpenAiAnalysisClient
    {
        return new OpenAiAnalysisClient(new AnalysisPromptBuilder());
    }

    public function test_throws_when_the_user_has_no_api_key(): void
    {
        $user = User::factory()->create();

        $this->expectException(AnalysisException::class);

        $this->client()->analyzeColumns($user, ['name'], [], 'gpt-5.4-mini');
    }

    public function test_parses_a_successful_structured_output_response(): void
    {
        $user = $this->userWithApiKey();

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode([
                        'columns' => [
                            ['source_column' => 'name', 'canonical_field' => 'name', 'data_type' => 'text', 'confidence' => 0.9, 'example_value' => 'Widget'],
                        ],
                        'overall_confidence' => 0.9,
                    ])]],
                ],
            ], 200),
        ]);

        $result = $this->client()->analyzeColumns($user, ['name'], [['name' => 'Widget']], 'gpt-5.4-mini');

        $this->assertSame(0.9, $result['overall_confidence']);
        $this->assertSame('name', $result['columns'][0]['source_column']);
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer sk-test-0123456789'));
    }

    public function test_throws_invalid_api_key_on_a_401_response(): void
    {
        $user = $this->userWithApiKey();
        Http::fake(['api.openai.com/*' => Http::response([], 401)]);

        try {
            $this->client()->analyzeColumns($user, ['name'], [], 'gpt-5.4-mini');
            $this->fail('Expected AnalysisException to be thrown.');
        } catch (AnalysisException $e) {
            $this->assertSame('invalid_api_key', $e->reason);
        }
    }

    public function test_throws_rate_limited_on_a_429_response(): void
    {
        $user = $this->userWithApiKey();
        Http::fake(['api.openai.com/*' => Http::response([], 429)]);

        try {
            $this->client()->analyzeColumns($user, ['name'], [], 'gpt-5.4-mini');
            $this->fail('Expected AnalysisException to be thrown.');
        } catch (AnalysisException $e) {
            $this->assertSame('rate_limited', $e->reason);
        }
    }

    public function test_throws_request_failed_on_a_generic_failure(): void
    {
        $user = $this->userWithApiKey();
        Http::fake(['api.openai.com/*' => Http::response([], 500)]);

        try {
            $this->client()->analyzeColumns($user, ['name'], [], 'gpt-5.4-mini');
            $this->fail('Expected AnalysisException to be thrown.');
        } catch (AnalysisException $e) {
            $this->assertSame('request_failed', $e->reason);
        }
    }

    public function test_throws_malformed_response_when_content_is_not_valid_json(): void
    {
        $user = $this->userWithApiKey();
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'not json']]],
            ], 200),
        ]);

        try {
            $this->client()->analyzeColumns($user, ['name'], [], 'gpt-5.4-mini');
            $this->fail('Expected AnalysisException to be thrown.');
        } catch (AnalysisException $e) {
            $this->assertSame('malformed_response', $e->reason);
        }
    }
}
