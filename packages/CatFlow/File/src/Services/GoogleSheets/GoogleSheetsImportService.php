<?php

namespace CatFlow\File\Services\GoogleSheets;

use CatFlow\File\Models\Dataset;
use CatFlow\File\Services\DatasetStorageService;
use CatFlow\File\Services\GoogleSheets\Fetchers\PrivateSheetFetcher;
use CatFlow\File\Services\GoogleSheets\Fetchers\PublicSheetFetcher;
use CatFlow\User\Models\User;

class GoogleSheetsImportService
{
    public function __construct(
        private readonly GoogleSheetsUrlParser $urlParser,
        private readonly PublicSheetFetcher $publicFetcher,
        private readonly PrivateSheetFetcher $privateFetcher,
        private readonly DatasetStorageService $storage,
    ) {
    }

    /**
     * Import a Google Sheet and cache it locally as a Dataset via the same
     * storage mechanics a direct upload uses.
     *
     * Only publicly-shared sheets are supported for now — $privateFetcher is
     * wired but not yet used; private-sheet import needs an expanded Google
     * OAuth scope + re-consent flow that hasn't been built yet.
     */
    public function import(User $user, string $url): Dataset
    {
        $parsed = $this->urlParser->parse($url);

        $content = $this->publicFetcher->fetch($parsed['spreadsheet_id'], $parsed['gid']);

        return $this->storage->storeGoogleSheetContent(
            user: $user,
            csvContent: $content,
            name: "Google Sheet {$parsed['spreadsheet_id']}",
            externalUrl: $url,
            spreadsheetId: $parsed['spreadsheet_id'],
            gid: $parsed['gid'],
        );
    }
}
