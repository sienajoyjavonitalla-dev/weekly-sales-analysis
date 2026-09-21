<?php

namespace App\Domain\WeeklyAnalysis\Imports\Services;

use App\Domain\WeeklyAnalysis\Imports\Support\NumericValueParser;

use App\Domain\WeeklyAnalysis\Classification\Services\SalesRowClassifier;
use App\Domain\WeeklyAnalysis\Imports\Contracts\WorkbookParser;
use App\Domain\WeeklyAnalysis\Imports\Data\ParsedWorkbook;
use App\Domain\WeeklyAnalysis\Imports\Enums\WorkbookType;
use App\Domain\WeeklyAnalysis\Imports\Parsers\IncomeStatementWorkbookParser;
use App\Domain\WeeklyAnalysis\Imports\Parsers\OpenOrdersWorkbookParser;
use App\Domain\WeeklyAnalysis\Imports\Parsers\PtdOrdersWorkbookParser;
use App\Domain\WeeklyAnalysis\Imports\Parsers\SalesAnalysisWorkbookParser;
use App\Domain\WeeklyAnalysis\Imports\Parsers\TotalSalesReportWorkbookParser;
use App\Domain\WeeklyAnalysis\Imports\Parsers\WeeklyMeterReportWorkbookParser;
use App\Models\ImportBatch;
use App\Models\IncomeStatementLine;
use App\Models\OrderRow;
use App\Models\SalesRow;
use App\Models\UploadedFile;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile as HttpUploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class WorkbookImportService
{
    /**
     * @var array<string, WorkbookParser>
     */
    private array $parsers;

    public function __construct(
        private readonly SalesRowClassifier $salesRowClassifier,
        private readonly UploadedFileDeletionService $uploadedFileDeletionService,
    ) {
        $this->parsers = $this->buildParserMap([
            new SalesAnalysisWorkbookParser(),
            new IncomeStatementWorkbookParser(),
            new TotalSalesReportWorkbookParser(),
            new WeeklyMeterReportWorkbookParser(),
            new OpenOrdersWorkbookParser(),
            new PtdOrdersWorkbookParser(),
        ]);
    }

    /**
     * @param  array<string, HttpUploadedFile>  $files
     * @return array{import_batch:ImportBatch, uploaded_files:array<int, UploadedFile>, summary:array<string, mixed>}
     */
    public function import(array $files, ?ImportBatch $importBatch = null, ?string $weekStart = null, ?string $weekEnding = null, ?int $userId = null): array
    {
        $this->uploadedFileDeletionService->deleteEmptyDraftBatches();

        if ($files === []) {
            throw ValidationException::withMessages([
                'workbooks' => ['Upload at least one workbook.'],
            ]);
        }

        return DB::transaction(function () use ($files, $importBatch, $weekStart, $weekEnding, $userId): array {
            [$resolvedWeekStart, $resolvedWeekEnding] = $this->resolveWeekRange($weekStart, $weekEnding, $files);

            if ($importBatch === null) {
                $importBatch = $this->createImportBatch(
                    weekStart: $resolvedWeekStart,
                    weekEnding: $resolvedWeekEnding,
                    userId: $userId,
                );
            }

            $uploadedFiles = [];
            $summary = [];
            $classifiedSalesRows = false;
            $salesAnalysisUploadedFile = null;

            foreach ($files as $type => $file) {
                $parsed = $this->parsers[$type]->parse($file->getRealPath());
                $uploadedFile = $this->storeUploadedFile($importBatch, $type, $file, $parsed);

                $this->clearPreviousRows($uploadedFile);
                $summary[$type] = $this->persistParsedRows($importBatch, $uploadedFile, $parsed);

                if ($type === WorkbookType::SalesAnalysis->value && $parsed->isValid()) {
                    $classifiedSalesRows = true;
                    $salesAnalysisUploadedFile = $uploadedFile;
                }

                $uploadedFiles[] = $uploadedFile->refresh();
            }

            if ($classifiedSalesRows) {
                $summary['classification'] = $this->salesRowClassifier->classifyBatch($importBatch);

                if (($summary['classification']['reconcilable_unmatched'] ?? 0) > 0 && $salesAnalysisUploadedFile !== null) {
                    $salesAnalysisUploadedFile->update(['status' => 'pending_reconcile']);
                    $salesAnalysisUploadedFile->refresh();
                }
            }

            return [
                'import_batch' => $importBatch->refresh()->load('uploadedFiles'),
                'uploaded_files' => $uploadedFiles,
                'summary' => $summary,
            ];
        });
    }

    private function createImportBatch(?string $weekStart, string $weekEnding, ?int $userId): ImportBatch
    {
        $sourceSystem = 'Traverse Global';

        $exists = ImportBatch::query()
            ->whereDate('week_ending', $weekEnding)
            ->where('source_system', $sourceSystem)
            ->whereHas('uploadedFiles')
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'week_ending' => [
                    'This week was already uploaded. Open the existing batch for that week, or choose a different week.',
                ],
            ]);
        }

        try {
            return ImportBatch::query()->create([
                'week_start' => $weekStart,
                'week_ending' => $weekEnding,
                'created_by_user_id' => $userId,
                'status' => 'draft',
                'source_system' => $sourceSystem,
            ]);
        } catch (QueryException $exception) {
            if ($this->isDuplicateWeekBatchException($exception)) {
                throw ValidationException::withMessages([
                    'week_ending' => [
                        'This week was already uploaded. Open the existing batch for that week, or choose a different week.',
                    ],
                ]);
            }

            throw $exception;
        }
    }

    private function isDuplicateWeekBatchException(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? '');
        $driverCode = (int) ($exception->errorInfo[1] ?? 0);
        $message = $exception->getMessage();

        return $sqlState === '23000'
            && (
                $driverCode === 1062
                || str_contains($message, 'import_batches_week_ending_source_system_unique')
                || str_contains($message, 'Duplicate entry')
            );
    }

    private function storeUploadedFile(
        ImportBatch $importBatch,
        string $type,
        HttpUploadedFile $file,
        ParsedWorkbook $parsed,
    ): UploadedFile {
        $extension = $file->getClientOriginalExtension() ?: 'xlsx';
        $baseName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $path = $file->storeAs(
            'uploads/'.$importBatch->id,
            $type.'-'.now()->format('YmdHis').'-'.$baseName.'.'.$extension,
            'local',
        );
        $absolutePath = Storage::disk('local')->path($path);

        return UploadedFile::query()->updateOrCreate(
            [
                'import_batch_id' => $importBatch->id,
                'file_type' => $type,
            ],
            [
                'original_name' => $file->getClientOriginalName(),
                'storage_path' => $path,
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize() ?: 0,
                'sha256_checksum' => hash_file('sha256', $absolutePath),
                'sheet_names' => $parsed->sheetNames,
                'status' => $parsed->isValid() ? 'imported' : 'validation_failed',
                'validation_errors' => array_map(
                    static fn ($issue): array => $issue->toArray(),
                    $parsed->issues,
                ),
            ],
        );
    }

    private function clearPreviousRows(UploadedFile $uploadedFile): void
    {
        SalesRow::query()->where('uploaded_file_id', $uploadedFile->id)->delete();
        OrderRow::query()->where('uploaded_file_id', $uploadedFile->id)->delete();
        IncomeStatementLine::query()->where('uploaded_file_id', $uploadedFile->id)->delete();
    }

    /**
     * @return array<string, int>
     */
    private function persistParsedRows(ImportBatch $importBatch, UploadedFile $uploadedFile, ParsedWorkbook $parsed): array
    {
        if (! $parsed->isValid()) {
            return ['imported_rows' => 0, 'errors' => count($parsed->errors())];
        }

        return match ($parsed->type) {
            WorkbookType::SalesAnalysis => ['imported_rows' => $this->persistSalesRows($importBatch, $uploadedFile, $parsed)],
            WorkbookType::IncomeStatement => ['imported_rows' => $this->persistIncomeStatementLines($importBatch, $uploadedFile, $parsed)],
            WorkbookType::OpenOrders, WorkbookType::PtdOrders => ['imported_rows' => $this->persistOrderRows($importBatch, $uploadedFile, $parsed)],
            WorkbookType::TotalSalesReport, WorkbookType::WeeklyMeterReport => ['imported_rows' => 0],
        };
    }

    private function persistSalesRows(ImportBatch $importBatch, UploadedFile $uploadedFile, ParsedWorkbook $parsed): int
    {
        foreach ($parsed->rows as $row) {
            SalesRow::query()->create([
                ...$row,
                'import_batch_id' => $importBatch->id,
                'uploaded_file_id' => $uploadedFile->id,
                'invoice_date' => $this->dateValue($row['invoice_date'] ?? null),
                'quantity_ordered' => $this->decimalValue($row['quantity_ordered'] ?? 0) ?? 0,
                'amount' => $this->decimalValue($row['amount'] ?? 0) ?? 0,
                'classification_status' => 'unmatched',
                'raw_values' => $row,
            ]);
        }

        return count($parsed->rows);
    }

    private function persistOrderRows(ImportBatch $importBatch, UploadedFile $uploadedFile, ParsedWorkbook $parsed): int
    {
        foreach ($parsed->rows as $row) {
            OrderRow::query()->create([
                ...$row,
                'import_batch_id' => $importBatch->id,
                'uploaded_file_id' => $uploadedFile->id,
                'transaction_date' => $this->dateValue($row['transaction_date'] ?? null),
                'quantity_ordered' => $this->decimalValue($row['quantity_ordered'] ?? null),
                'amount' => $this->decimalValue($row['amount'] ?? 0) ?? 0,
                'raw_values' => $row,
            ]);
        }

        return count($parsed->rows);
    }

    private function persistIncomeStatementLines(ImportBatch $importBatch, UploadedFile $uploadedFile, ParsedWorkbook $parsed): int
    {
        foreach ($parsed->rows as $row) {
            IncomeStatementLine::query()->create([
                ...$row,
                'import_batch_id' => $importBatch->id,
                'uploaded_file_id' => $uploadedFile->id,
                'current_period_amount' => $this->decimalValue($row['current_period_amount'] ?? 0) ?? 0,
                'current_period_percent' => $this->decimalValue($row['current_period_percent'] ?? null),
                'year_to_date_amount' => $this->decimalValue($row['year_to_date_amount'] ?? 0) ?? 0,
                'year_to_date_percent' => $this->decimalValue($row['year_to_date_percent'] ?? null),
                'raw_values' => $row,
            ]);
        }

        return count($parsed->rows);
    }

    /**
     * @param  array<string, HttpUploadedFile>  $files
     * @return array{0: string, 1: string}
     */
    private function resolveWeekRange(?string $weekStart, ?string $weekEnding, array $files): array
    {
        if ($weekEnding !== null && $weekEnding !== '') {
            $end = Carbon::parse($weekEnding)->toDateString();
            $start = $weekStart !== null && $weekStart !== ''
                ? Carbon::parse($weekStart)->toDateString()
                : Carbon::parse($end)->subDays(6)->toDateString();

            if (Carbon::parse($start)->gt(Carbon::parse($end))) {
                return [$end, $start];
            }

            return [$start, $end];
        }

        foreach ($files as $file) {
            if (preg_match('/(\d{2})-(\d{2})-(\d{4})/', $file->getClientOriginalName(), $matches)) {
                $end = Carbon::createFromFormat('m-d-Y', "{$matches[1]}-{$matches[2]}-{$matches[3]}")->toDateString();

                return [Carbon::parse($end)->subDays(6)->toDateString(), $end];
            }
        }

        $end = now()->toDateString();

        return [Carbon::parse($end)->subDays(6)->toDateString(), $end];
    }

    private function dateValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->toDateString();
        }

        if (is_numeric($value) && (float) $value > 20000) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->toDateString();
        }

        return Carbon::parse((string) $value)->toDateString();
    }

    private function decimalValue(mixed $value): ?float
    {
        return NumericValueParser::parse($value);
    }

    /**
     * @param  array<int, WorkbookParser>  $parsers
     * @return array<string, WorkbookParser>
     */
    private function buildParserMap(array $parsers): array
    {
        $map = [];

        foreach ($parsers as $parser) {
            $map[$parser->type()->value] = $parser;
        }

        return $map;
    }
}
