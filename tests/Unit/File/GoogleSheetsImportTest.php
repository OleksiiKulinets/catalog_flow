<?php

namespace Tests\Unit\File;

use CatFlow\Auth\Services\GoogleTokenRefresher;
use CatFlow\File\Services\GoogleSheets\Fetchers\PrivateSheetFetcher;
use CatFlow\File\Services\GoogleSheets\Fetchers\PublicSheetFetcher;
use CatFlow\File\Services\GoogleSheets\GoogleSheetsImportException;
use CatFlow\File\Services\GoogleSheets\GoogleSheetsUrlParser;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class GoogleSheetsImportTest extends TestCase
{
    public function test_url_parser_extracts_spreadsheet_id_and_gid(): void
    {
        $result = (new GoogleSheetsUrlParser())->parse(
            'https://docs.google.com/spreadsheets/d/abc123/edit#gid=456'
        );

        $this->assertSame('abc123', $result['spreadsheet_id']);
        $this->assertSame('456', $result['gid']);
    }

    public function test_url_parser_handles_url_without_gid(): void
    {
        $result = (new GoogleSheetsUrlParser())->parse(
            'https://docs.google.com/spreadsheets/d/abc123/edit'
        );

        $this->assertSame('abc123', $result['spreadsheet_id']);
        $this->assertNull($result['gid']);
    }

    public function test_url_parser_throws_for_invalid_url(): void
    {
        $this->expectException(GoogleSheetsImportException::class);

        (new GoogleSheetsUrlParser())->parse('https://example.com/not-a-sheet');
    }

    public function test_url_parser_rejects_a_non_google_host_even_with_a_matching_path_shape(): void
    {
        $this->expectException(GoogleSheetsImportException::class);

        (new GoogleSheetsUrlParser())->parse('https://evil.example/spreadsheets/d/abc123/edit');
    }

    public function test_public_fetcher_returns_csv_content(): void
    {
        Http::fake([
            'docs.google.com/*' => Http::response("a,b\n1,2\n", 200, ['Content-Type' => 'text/csv']),
        ]);

        $content = (new PublicSheetFetcher())->fetch('abc123', null);

        $this->assertSame("a,b\n1,2\n", $content);
    }

    public function test_public_fetcher_omits_gid_query_param_when_none_was_parsed(): void
    {
        // Google's export endpoint 400s on an explicit gid=0 if the sheet's
        // first tab doesn't actually have gid 0 — omitting the param
        // entirely is the only way to reliably get the default tab.
        Http::fake([
            'docs.google.com/*' => Http::response("a,b\n1,2\n", 200, ['Content-Type' => 'text/csv']),
        ]);

        (new PublicSheetFetcher())->fetch('abc123', null);

        Http::assertSent(fn ($request) => ! str_contains($request->url(), 'gid='));
    }

    public function test_public_fetcher_includes_gid_query_param_when_one_was_parsed(): void
    {
        Http::fake([
            'docs.google.com/*' => Http::response("a,b\n1,2\n", 200, ['Content-Type' => 'text/csv']),
        ]);

        (new PublicSheetFetcher())->fetch('abc123', '456');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'gid=456'));
    }

    public function test_public_fetcher_throws_when_sheet_is_not_public(): void
    {
        Http::fake([
            'docs.google.com/*' => Http::response('<html>sign in</html>', 200, ['Content-Type' => 'text/html']),
        ]);

        $this->expectException(GoogleSheetsImportException::class);

        (new PublicSheetFetcher())->fetch('abc123', null);
    }

    public function test_public_fetcher_throws_when_sheet_exceeds_the_size_cap(): void
    {
        Http::fake([
            'docs.google.com/*' => Http::response(
                str_repeat('a', 26 * 1024 * 1024),
                200,
                ['Content-Type' => 'text/csv']
            ),
        ]);

        $this->expectException(GoogleSheetsImportException::class);

        (new PublicSheetFetcher())->fetch('abc123', null);
    }

    public function test_private_fetcher_is_not_implemented_yet(): void
    {
        $this->expectException(RuntimeException::class);

        (new PrivateSheetFetcher(new GoogleTokenRefresher()))->fetch('abc123', null);
    }
}
