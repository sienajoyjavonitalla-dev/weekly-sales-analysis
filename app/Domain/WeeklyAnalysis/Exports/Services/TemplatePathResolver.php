<?php

namespace App\Domain\WeeklyAnalysis\Exports\Services;

use App\Domain\WeeklyAnalysis\Storage\WorkbookStorage;
use App\Models\ImportBatch;
use App\Models\UploadedFile;

class TemplatePathResolver
{
    public function __construct(
        private readonly WorkbookStorage $workbookStorage,
    ) {
    }

    public function exists(ImportBatch $importBatch, string $fileType): bool
    {
        $uploadedFile = $this->findUploadedFile($importBatch, $fileType);

        return $uploadedFile !== null
            && is_string($uploadedFile->storage_path)
            && $uploadedFile->storage_path !== ''
            && $this->workbookStorage->exists($uploadedFile->storage_path);
    }

    /**
     * @template TReturn
     *
     * @param  callable(string): TReturn  $callback
     * @return TReturn
     */
    public function withLocalCopy(ImportBatch $importBatch, string $fileType, callable $callback): mixed
    {
        $uploadedFile = $this->findUploadedFile($importBatch, $fileType);

        if ($uploadedFile === null || ! is_string($uploadedFile->storage_path) || $uploadedFile->storage_path === '') {
            throw new \RuntimeException("Uploaded template [{$fileType}] was not found for this import batch.");
        }

        return $this->workbookStorage->withLocalCopy($uploadedFile->storage_path, $callback);
    }

    private function findUploadedFile(ImportBatch $importBatch, string $fileType): ?UploadedFile
    {
        return UploadedFile::query()
            ->where('import_batch_id', $importBatch->id)
            ->where('file_type', $fileType)
            ->first();
    }
}
