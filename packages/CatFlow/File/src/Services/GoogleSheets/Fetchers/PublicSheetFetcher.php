<?php

namespace CatFlow\File\Services\GoogleSheets\Fetchers;

use CatFlow\File\Services\GoogleSheets\GoogleSheetsImportException;
use Illuminate\Support\Facades\Http;

/**
 * Fetches a publicly-shared ("Anyone with the link") Google Sheet.
 *
 * No Google API/OAuth needed for this path — Google Sheets exposes a plain
 * CSV export URL for public sheets:
 * https://docs.google.com/spreadsheets/d/{id}/export?format=csv&gid={gid}
 */
class PublicSheetFetcher implements SheetFetcherInterface
{
    /**
     * Same cap as a direct file upload (StoreBatchRequest's dataset
     * max:25600 KB) — a sheet has no equivalent client-side size check
     * before we fetch it, so this is the only thing stopping an enormous
     * public sheet from exhausting memory/disk.
     */
    private const MAX_BYTES = 25 * 1024 * 1024;

    public function fetch(string $spreadsheetId, ?string $gid): string
    {
        // Google's export endpoint treats an explicit gid=0 as "that exact
        // tab id" (400s if the sheet's first tab doesn't happen to be gid 0)
        // whereas omitting gid entirely correctly falls back to the default
        // tab — so only send it when we actually parsed one from the URL.
        $query = array_filter([
            'format' => 'csv',
            'gid' => $gid,
        ], fn ($value) => $value !== null);

        $response = Http::timeout(15)->get(
            "https://docs.google.com/spreadsheets/d/{$spreadsheetId}/export",
            $query
        );

        if ($response->failed()) {
            throw GoogleSheetsImportException::fetchFailed();
        }

        $body = $response->body();

        // A non-public (or deleted) sheet doesn't 4xx here — Google responds
        // 200 with an HTML sign-in/error page instead of a CSV body.
        if (! str_contains((string) $response->header('Content-Type'), 'text/csv')
            || str_starts_with(ltrim($body), '<')) {
            throw GoogleSheetsImportException::notPubliclyAccessible();
        }

        if (strlen($body) > self::MAX_BYTES) {
            throw GoogleSheetsImportException::tooLarge();
        }

        return $body;
    }
}
