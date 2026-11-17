<?php

namespace App\Domain\WeeklyAnalysis\Storage;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile as HttpUploadedFile;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToCheckExistence;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class WorkbookStorage
{
    public function disk(): Filesystem
    {
        return Storage::disk($this->diskName());
    }

    public function diskName(): string
    {
        return (string) config('weekly-analysis.disk', 'local');
    }

    public function exists(string $path): bool
    {
        try {
            return $this->disk()->exists($path);
        } catch (UnableToCheckExistence|Throwable $exception) {
            if ($this->isMissingObjectException($exception)) {
                return false;
            }

            throw $exception;
        }
    }

    public function delete(string $path): bool
    {
        return $this->disk()->delete($path);
    }

    /**
     * Best-effort delete for the configured disk and, during S3 cutover,
     * any leftover local private copy of the same relative path.
     */
    public function deleteIfPresent(string $path): void
    {
        if ($path === '') {
            return;
        }

        $this->deleteFromDiskQuietly($this->diskName(), $path);

        if ($this->diskName() !== 'local') {
            $this->deleteFromDiskQuietly('local', $path);
        }
    }

    public function deleteDirectory(string $path): bool
    {
        try {
            return $this->disk()->deleteDirectory($path);
        } catch (UnableToDeleteDirectory|Throwable $exception) {
            if ($this->diskName() !== 'local') {
                try {
                    Storage::disk('local')->deleteDirectory($path);
                } catch (Throwable) {
                    // Ignore local cleanup failures during cutover.
                }
            }

            return false;
        }
    }

    public function put(string $path, mixed $contents, array $options = []): bool|string
    {
        return $this->disk()->put($path, $contents, $options);
    }

    public function storeUploadedFile(HttpUploadedFile $file, string $directory, string $fileName): string
    {
        try {
            $path = $file->storeAs($directory, $fileName, [
                'disk' => $this->diskName(),
                'visibility' => 'private',
            ]);
        } catch (Throwable $exception) {
            throw new \RuntimeException(
                'Failed to store uploaded workbook on '.$this->diskName().': '.$this->storageErrorMessage($exception),
                previous: $exception,
            );
        }

        if (! is_string($path) || $path === '') {
            throw new \RuntimeException('Failed to store uploaded workbook on '.$this->diskName().'.');
        }

        return $path;
    }

    public function checksumFromPath(string $absolutePath): string
    {
        $checksum = hash_file('sha256', $absolutePath);

        if (! is_string($checksum) || $checksum === '') {
            throw new \RuntimeException('Failed to checksum workbook file.');
        }

        return $checksum;
    }

    public function checksum(string $storagePath): string
    {
        $stream = $this->disk()->readStream($storagePath);

        if ($stream === false || $stream === null) {
            throw new \RuntimeException("Unable to read workbook at [{$storagePath}] for checksum.");
        }

        try {
            $context = hash_init('sha256');
            hash_update_stream($context, $stream);

            return hash_final($context);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    /**
     * @template TReturn
     *
     * @param  callable(string): TReturn  $callback
     * @return TReturn
     */
    public function withLocalCopy(string $storagePath, callable $callback): mixed
    {
        if (! $this->exists($storagePath)) {
            throw new \RuntimeException("Workbook was not found at [{$storagePath}].");
        }

        if ($this->diskName() === 'local') {
            return $callback($this->disk()->path($storagePath));
        }

        $tempPath = $this->makeTempPath(pathinfo($storagePath, PATHINFO_EXTENSION) ?: 'xlsx');

        try {
            $stream = $this->disk()->readStream($storagePath);

            if ($stream === false || $stream === null) {
                throw new \RuntimeException("Unable to read workbook at [{$storagePath}].");
            }

            try {
                $destination = fopen($tempPath, 'w+b');

                if ($destination === false) {
                    throw new \RuntimeException('Unable to create a temporary workbook copy.');
                }

                try {
                    stream_copy_to_stream($stream, $destination);
                } finally {
                    fclose($destination);
                }
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            return $callback($tempPath);
        } finally {
            if (is_file($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    /**
     * @return array{storage_path: string, sha256_checksum: string}
     */
    public function putSpreadsheet(string $storagePath, Spreadsheet $spreadsheet): array
    {
        $tempPath = $this->makeTempPath('xlsx');

        try {
            IOFactory::createWriter($spreadsheet, 'Xlsx')->save($tempPath);
            $checksum = $this->checksumFromPath($tempPath);
            $stream = fopen($tempPath, 'rb');

            if ($stream === false) {
                throw new \RuntimeException('Unable to open generated workbook for upload.');
            }

            try {
                $stored = $this->disk()->put($storagePath, $stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            if ($stored !== true) {
                throw new \RuntimeException("Failed to store generated workbook at [{$storagePath}].");
            }

            return [
                'storage_path' => $storagePath,
                'sha256_checksum' => $checksum,
            ];
        } finally {
            if (is_file($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    public function download(string $storagePath, string $name, array $headers = []): StreamedResponse
    {
        return $this->disk()->download($storagePath, $name, $headers);
    }

    private function makeTempPath(string $extension): string
    {
        $extension = ltrim($extension, '.');
        $tempPath = tempnam(sys_get_temp_dir(), 'wsa_');

        if ($tempPath === false) {
            throw new \RuntimeException('Unable to allocate a temporary workbook path.');
        }

        $pathWithExtension = $tempPath.'.'.$extension;

        if (! @rename($tempPath, $pathWithExtension)) {
            @unlink($tempPath);

            throw new \RuntimeException('Unable to prepare a temporary workbook path.');
        }

        return $pathWithExtension;
    }

    private function deleteFromDiskQuietly(string $diskName, string $path): void
    {
        try {
            Storage::disk($diskName)->delete($path);
        } catch (UnableToDeleteFile|Throwable) {
            // Missing objects or cutover leftovers should not block DB cleanup.
        }
    }

    private function isMissingObjectException(Throwable $exception): bool
    {
        $messages = [$exception->getMessage()];

        if ($exception->getPrevious() !== null) {
            $messages[] = $exception->getPrevious()->getMessage();
        }

        foreach ($messages as $message) {
            if (str_contains($message, '404') || str_contains(strtolower($message), 'not found')) {
                return true;
            }
        }

        return false;
    }

    private function storageErrorMessage(Throwable $exception): string
    {
        $message = $exception->getMessage();
        $previous = $exception->getPrevious();

        while ($previous !== null) {
            $message = $previous->getMessage();
            $previous = $previous->getPrevious();
        }

        if (str_contains($message, 'AccessDenied') || str_contains($message, 'not authorized')) {
            return 'S3 access denied. Ensure the IAM user can PutObject/GetObject/DeleteObject on this bucket.';
        }

        return $message;
    }
}
