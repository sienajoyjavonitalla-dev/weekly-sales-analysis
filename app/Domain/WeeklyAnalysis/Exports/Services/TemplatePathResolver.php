<?php

namespace App\Domain\WeeklyAnalysis\Exports\Services;

use App\Models\ImportBatch;
use App\Models\UploadedFile;

class TemplatePathResolver
{
    public function resolve(ImportBatch $importBatch, string $fileType): ?string
    {
        $uploadedFile = UploadedFile::query()
            ->where('import_batch_id', $importBatch->id)
            ->where('file_type', $fileType)
            ->first();

        if ($uploadedFile === null) {
            return null;
        }

        if (is_file($uploadedFile->storage_path)) {
            return $uploadedFile->storage_path;
        }

        $privatePath = storage_path('app/private/'.$uploadedFile->storage_path);

        if (is_file($privatePath)) {
            return $privatePath;
        }

        $storagePath = storage_path('app/'.$uploadedFile->storage_path);

        return is_file($storagePath) ? $storagePath : null;
    }
}
