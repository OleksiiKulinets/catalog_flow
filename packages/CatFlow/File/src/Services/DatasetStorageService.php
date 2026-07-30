<?php

namespace CatFlow\File\Services;

use CatFlow\File\Models\Dataset;
use CatFlow\File\Repositories\DatasetRepository;
use CatFlow\File\Services\Parsers\DatasetParserFactory;
use CatFlow\User\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DatasetStorageService
{
    public function __construct(
        private readonly DatasetRepository $datasets,
        private readonly DatasetParserFactory $parsers,
    ) {
    }

    /**
     * Store an uploaded file on the local disk and record it as a Dataset.
     */
    public function storeUploadedFile(User $user, UploadedFile $file): Dataset
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $sourceFormat = $extension === 'xls' ? 'xlsx' : $extension;

        $storagePath = $file->storeAs(
            "datasets/{$user->id}",
            Str::uuid()->toString().'.'.$extension,
            'local'
        );

        return $this->finalize($this->datasets->create([
            'user_id' => $user->id,
            'name' => $file->getClientOriginalName(),
            'source_type' => Dataset::SOURCE_UPLOAD,
            'source_format' => $sourceFormat,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'extension' => $extension,
            'storage_path' => $storagePath,
            'file_size' => $file->getSize(),
        ]));
    }

    /**
     * Cache a fetched Google Sheet's CSV content locally as a Dataset, using
     * the same storage mechanics a direct upload uses rather than reading
     * the sheet live from the API on every access.
     */
    public function storeGoogleSheetContent(
        User $user,
        string $csvContent,
        string $name,
        string $externalUrl,
        string $spreadsheetId,
        ?string $gid,
    ): Dataset {
        $storagePath = "datasets/{$user->id}/".Str::uuid()->toString().'.csv';
        Storage::disk('local')->put($storagePath, $csvContent);

        return $this->finalize($this->datasets->create([
            'user_id' => $user->id,
            'name' => $name,
            'source_type' => Dataset::SOURCE_GOOGLE_SHEET,
            'source_format' => 'csv',
            'storage_path' => $storagePath,
            'file_size' => strlen($csvContent),
            'external_url' => $externalUrl,
            'spreadsheet_id' => $spreadsheetId,
            'sheet_gid' => $gid,
            'synced_at' => now(),
        ]));
    }

    private function finalize(Dataset $dataset): Dataset
    {
        $dataset->update([
            'rows_count' => $this->parsers->make($dataset)->countRows($dataset),
        ]);

        return $dataset;
    }
}
