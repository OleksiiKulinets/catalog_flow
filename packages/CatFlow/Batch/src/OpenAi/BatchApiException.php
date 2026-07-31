<?php

namespace CatFlow\Batch\OpenAi;

use RuntimeException;

/**
 * Carries a machine-readable reason code alongside the exception message,
 * same shape as CatFlow\Analysis\OpenAi\AnalysisException.
 */
class BatchApiException extends RuntimeException
{
    private function __construct(public readonly string $reason, string $message)
    {
        parent::__construct($message);
    }

    public static function missingApiKey(): self
    {
        return new self('missing_api_key', 'No OpenAI API key is configured for this user.');
    }

    public static function invalidApiKey(): self
    {
        return new self('invalid_api_key', 'The OpenAI API key was rejected.');
    }

    public static function rateLimited(): self
    {
        return new self('rate_limited', 'The OpenAI API rate limit was exceeded.');
    }

    public static function requestFailed(): self
    {
        return new self('request_failed', 'The request to OpenAI failed.');
    }

    public static function malformedResponse(): self
    {
        return new self('malformed_response', 'OpenAI returned a response in an unexpected shape.');
    }
}
