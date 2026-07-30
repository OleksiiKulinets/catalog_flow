<?php

namespace CatFlow\File\Services\GoogleSheets\Fetchers;

use CatFlow\Auth\Services\GoogleTokenRefresher;
use RuntimeException;

/**
 * Fetches a private Google Sheet via the Sheets API v4, authenticated with
 * the user's connected Google account (CatFlow\Auth\Models\GoogleAccount).
 */
class PrivateSheetFetcher implements SheetFetcherInterface
{
    public function __construct(private readonly GoogleTokenRefresher $tokenRefresher)
    {
    }

    public function fetch(string $spreadsheetId, ?string $gid): string
    {
        throw new RuntimeException('Private Google Sheets import is not implemented yet.');
    }
}
