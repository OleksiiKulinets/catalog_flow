<?php

namespace CatFlow\File\Repositories;

use CatFlow\File\Models\Dataset;
use CatFlow\User\Models\User;
use Illuminate\Support\Facades\Storage;

class DatasetRepository
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Dataset
    {
        return Dataset::create($attributes);
    }

    public function findForUser(User $user, int $id): ?Dataset
    {
        return Dataset::where('user_id', $user->id)->find($id);
    }

    /**
     * Delete a dataset and, if it has one, the file backing it on disk.
     */
    public function delete(Dataset $dataset): void
    {
        if ($dataset->storage_path) {
            Storage::disk('local')->delete($dataset->storage_path);
        }

        $dataset->delete();
    }
}
