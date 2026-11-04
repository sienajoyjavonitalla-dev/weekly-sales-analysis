<?php

namespace App\Domain\WeeklyAnalysis\Reports;

use App\Domain\WeeklyAnalysis\Imports\Enums\WorkbookType;
use App\Models\ImportBatch;
use App\Models\ImportBatchFeeAdjustment;
use App\Models\ProductCategory;
use App\Models\SalesRow;
use Illuminate\Support\Collection;

class MarketplaceFeesAllocator
{
    /**
     * @var list<string>
     */
    private const AMAZON_COMBINED_RHP_MISCELLANEOUS_CODES = [
        'rhp_miscellaneous',
        'rhp_miscellaneous_l6',
        'misc_on_total_sales_report',
    ];

    /**
     * @var list<string>
     */
    private const AMAZON_COMBINED_RHP_MISCELLANEOUS_NAMES = [
        'rhp miscellaneous',
        'rhp miscellaneous l6',
        'misc (on total sales report)',
    ];

    /**
     * Sales can sit on BT Smart Loggers; allocated Amazon/Walmart fees belong on RHP L6 Misc.
     *
     * @var array<string, string>
     */
    private const FEE_METER_LABEL_REMAP = [
        'BT Smart Loggers' => 'RHP L6 Misc',
    ];

    /**
     * Allocated marketplace fee (generated report column F) keyed by meter model label.
     *
     * @return array<string, float>
     */
    public function allocatedByMeterLabel(ImportBatch $importBatch, WorkbookType $workbookType): array
    {
        if (! $workbookType->isMarketplaceFees() || ! $this->hasUploadedFile($importBatch, $workbookType)) {
            return [];
        }

        $manualTotal = $this->manualFeeTotal($importBatch, $workbookType);
        if ($manualTotal == 0.0) {
            return [];
        }

        $categoriesByCode = ProductCategory::query()
            ->where('is_active', true)
            ->get()
            ->keyBy('code');

        $rows = SalesRow::query()
            ->with('productCategory')
            ->forWorkbookType($workbookType->value)
            ->where('import_batch_id', $importBatch->id)
            ->get();

        if ($workbookType === WorkbookType::AmazonFees) {
            $rows = $rows
                ->reject(fn (SalesRow $row): bool => $this->isExcludedShippingRow($row))
                ->values();
        }

        $rows = $rows
            ->filter(fn (SalesRow $row): bool => $row->productCategory === null || $row->productCategory->is_active)
            ->values();

        $batchAmount = (float) $rows->sum(fn (SalesRow $row): float => (float) $row->amount);
        if ($batchAmount == 0.0) {
            return [];
        }

        $amountsByLabel = [];

        foreach ($rows as $row) {
            $label = $this->meterLabelForRow($row, $categoriesByCode, $workbookType);
            if ($label === null || $label === '') {
                continue;
            }

            $amountsByLabel[$label] = ($amountsByLabel[$label] ?? 0.0) + (float) $row->amount;
        }

        $allocated = [];

        foreach ($amountsByLabel as $label => $groupAmount) {
            $allocated[$label] = round($manualTotal * ($groupAmount / $batchAmount), 2);
        }

        return $allocated;
    }

    /**
     * @param  Collection<int, ImportBatch>  $batches
     * @return array<string, list<array{amount:float, sort_order:int, source:string}>>
     */
    public function additionalFeesByLabel(Collection $batches): array
    {
        $grouped = [];

        foreach ($batches as $batch) {
            foreach ([WorkbookType::AmazonFees, WorkbookType::WalmartFees] as $workbookType) {
                foreach ($this->allocatedByMeterLabel($batch, $workbookType) as $label => $amount) {
                    if ($amount == 0.0) {
                        continue;
                    }

                    $grouped[$label][] = [
                        // Meter ADDT'L FEES is the opposite sign of generated fees column F.
                        'amount' => round(-$amount, 2),
                        'sort_order' => count($grouped[$label] ?? []),
                        'source' => (string) $workbookType->marketplace(),
                    ];
                }
            }
        }

        return $grouped;
    }

    private function hasUploadedFile(ImportBatch $importBatch, WorkbookType $workbookType): bool
    {
        if ($importBatch->relationLoaded('uploadedFiles')) {
            return $importBatch->uploadedFiles->contains(
                fn ($file): bool => $file->file_type === $workbookType->value && $file->status === 'imported',
            );
        }

        return $importBatch->uploadedFiles()
            ->where('file_type', $workbookType->value)
            ->where('status', 'imported')
            ->exists();
    }

    private function manualFeeTotal(ImportBatch $importBatch, WorkbookType $workbookType): float
    {
        $marketplace = $workbookType->marketplace();
        if ($marketplace === null) {
            return 0.0;
        }

        return round((float) ImportBatchFeeAdjustment::query()
            ->where('import_batch_id', $importBatch->id)
            ->where('marketplace', $marketplace)
            ->sum('amount'), 2);
    }

    /**
     * @param  Collection<string, ProductCategory>  $categoriesByCode
     */
    private function meterLabelForRow(
        SalesRow $row,
        Collection $categoriesByCode,
        WorkbookType $workbookType,
    ): ?string {
        $category = $row->productCategory;
        if ($category === null) {
            return null;
        }

        if ($this->combinesIntoRhpMiscellaneous($category, $workbookType)) {
            $label = $categoriesByCode->get('rhp_miscellaneous')?->weekly_meter_row_label
                ?? $category->weekly_meter_row_label;

            return $this->usableMeterLabel($label);
        }

        return $this->usableMeterLabel($category->weekly_meter_row_label);
    }

    private function usableMeterLabel(?string $label): ?string
    {
        $trimmed = trim((string) $label);
        if ($trimmed === '') {
            return null;
        }

        return self::FEE_METER_LABEL_REMAP[$trimmed] ?? $trimmed;
    }

    private function combinesIntoRhpMiscellaneous(?ProductCategory $category, WorkbookType $workbookType): bool
    {
        if ($workbookType !== WorkbookType::AmazonFees || $category === null) {
            return false;
        }

        if (in_array($category->code, self::AMAZON_COMBINED_RHP_MISCELLANEOUS_CODES, true)) {
            return true;
        }

        return in_array(
            strtolower(trim($category->name)),
            self::AMAZON_COMBINED_RHP_MISCELLANEOUS_NAMES,
            true,
        );
    }

    private function isExcludedShippingRow(SalesRow $row): bool
    {
        $itemId = strtoupper(trim((string) $row->item_id));
        $itemId = preg_replace('/[\s_\-]+/', '_', $itemId) ?? $itemId;

        return str_starts_with($itemId, 'SHIP');
    }
}
