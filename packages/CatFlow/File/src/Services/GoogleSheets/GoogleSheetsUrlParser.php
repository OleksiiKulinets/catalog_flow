<?php

namespace CatFlow\File\Services\GoogleSheets;

class GoogleSheetsUrlParser
{
    /**
     * Extract the spreadsheet ID and (optional) sheet gid from a pasted
     * Google Sheets URL, e.g.
     * https://docs.google.com/spreadsheets/d/{spreadsheet_id}/edit#gid={gid}
     *
     * @return array{spreadsheet_id: string, gid: ?string}
     */
    public function parse(string $url): array
    {
        // Anchor to the real host, not just the path shape — otherwise
        // "https://evil.example/spreadsheets/d/xyz" would parse "successfully"
        // and only fail later, confusingly, at the network layer.
        if (parse_url($url, PHP_URL_HOST) !== 'docs.google.com') {
            throw GoogleSheetsImportException::invalidUrl();
        }

        if (! preg_match('~/spreadsheets/d/([a-zA-Z0-9-_]+)~', $url, $matches)) {
            throw GoogleSheetsImportException::invalidUrl();
        }

        $gid = null;
        if (preg_match('~[#&?]gid=(\d+)~', $url, $gidMatches)) {
            $gid = $gidMatches[1];
        }

        return [
            'spreadsheet_id' => $matches[1],
            'gid' => $gid,
        ];
    }
}
