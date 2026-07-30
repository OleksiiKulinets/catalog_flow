<?php

namespace CatFlow\File\Services\GoogleSheets\Fetchers;

interface SheetFetcherInterface
{
    /**
     * Fetch the sheet's contents as raw CSV text.
     */
    public function fetch(string $spreadsheetId, ?string $gid): string;
}
