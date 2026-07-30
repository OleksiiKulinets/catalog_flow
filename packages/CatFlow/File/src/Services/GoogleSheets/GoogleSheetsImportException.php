<?php

namespace CatFlow\File\Services\GoogleSheets;

use RuntimeException;

/**
 * Carries a machine-readable reason code alongside the exception message so
 * the controller can map it to a localized, user-facing message
 * (app.batches.batches.create.google_sheets_errors.{reason}) instead of
 * showing this technical message directly.
 */
class GoogleSheetsImportException extends RuntimeException
{
    private function __construct(public readonly string $reason, string $message)
    {
        parent::__construct($message);
    }

    public static function invalidUrl(): self
    {
        return new self('invalid_url', 'The given value is not a Google Sheets URL.');
    }

    public static function notPubliclyAccessible(): self
    {
        return new self('not_public', 'The Google Sheet is not publicly accessible.');
    }

    public static function fetchFailed(): self
    {
        return new self('fetch_failed', 'Failed to fetch the Google Sheet.');
    }

    public static function tooLarge(): self
    {
        return new self('too_large', 'The Google Sheet is too large to import.');
    }
}
