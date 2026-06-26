<?php

namespace App\Domain\WeeklyAnalysis\Exports\Services;

use App\Models\GeneratedReport;
use App\Models\ImportBatch;
use App\Models\SalesRow;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesAnalysisWorkbookExporter
{
    private const HEADERS = [
        'Item ID',
        'Description',
        'Customer ID',
        'Customer Name',
        'Invoice Number',
        'Sales Rep 1 ID',
        'Country',
        'Bill to State',
        'Invoice Date',
        'Qty Order Sell',
        'Amount',
        'Category',
    ];

    public function __construct(
        private readonly GeneratedReportRecorder $recorder,
        private readonly SpreadsheetValueSanitizer $sanitizer,
    ) {
    }

    public function export(ImportBatch $importBatch, ?int $generatedByUserId = null): GeneratedReport
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator(config('app.name'))
            ->setTitle('Weekly Sales Analysis '.$importBatch->week_ending->format('m-d-Y'));

        $this->writeSalesSheet($spreadsheet->getActiveSheet(), $importBatch, 'RHP', 'rhp');
        $this->writeSalesSheet($spreadsheet->createSheet(), $importBatch, 'Parts & TSD', 'parts_tsd');
        $this->writeSalesSheet($spreadsheet->createSheet(), $importBatch, 'Sheet', null);

        return $this->recorder->save(
            importBatch: $importBatch,
            reportType: 'sales_analysis',
            spreadsheet: $spreadsheet,
            generatedByUserId: $generatedByUserId,
            summary: [
                'rhp_rows' => $this->rowCount($importBatch, 'rhp'),
                'parts_tsd_rows' => $this->rowCount($importBatch, 'parts_tsd'),
                'raw_rows' => $this->rowCount($importBatch, null),
            ],
        );
    }

    private function writeSalesSheet(Worksheet $sheet, ImportBatch $importBatch, string $title, ?string $bucket): void
    {
        $sheet->setTitle($title);
        $sheet->fromArray([strtoupper($title).' SALES ANALYSIS - '.$importBatch->week_ending->format('m/d/Y')], null, 'A1');
        $sheet->fromArray(self::HEADERS, null, 'A2');

        $query = SalesRow::query()
            ->with('productCategory')
            ->where('import_batch_id', $importBatch->id)
            ->orderBy('source_row_number');

        if ($bucket !== null) {
            $query->where('source_bucket', $bucket);
        }

        $rowNumber = 3;
        $amountTotal = 0.0;
        $quantityTotal = 0.0;

        foreach ($query->get() as $salesRow) {
            $amountTotal += (float) $salesRow->amount;
            $quantityTotal += (float) $salesRow->quantity_ordered;

            $sheet->fromArray([
                $this->sanitizer->sanitize($salesRow->item_id),
                $this->sanitizer->sanitize($salesRow->description),
                $this->sanitizer->sanitize($salesRow->customer_id),
                $this->sanitizer->sanitize($salesRow->customer_name),
                $this->sanitizer->sanitize($salesRow->invoice_number),
                $this->sanitizer->sanitize($salesRow->sales_rep_id),
                $this->sanitizer->sanitize($salesRow->country),
                $this->sanitizer->sanitize($salesRow->bill_to_state),
                $salesRow->invoice_date?->format('m/d/Y'),
                (float) $salesRow->quantity_ordered,
                (float) $salesRow->amount,
                $this->sanitizer->sanitize($salesRow->productCategory?->name),
            ], null, 'A'.$rowNumber);

            $rowNumber++;
        }

        $sheet->setCellValue('I'.$rowNumber, 'Total');
        $sheet->setCellValue('J'.$rowNumber, $quantityTotal);
        $sheet->setCellValue('K'.$rowNumber, $amountTotal);
        $sheet->freezePane('A3');
        $sheet->setAutoFilter('A2:L'.max(2, $rowNumber - 1));
    }

    private function rowCount(ImportBatch $importBatch, ?string $bucket): int
    {
        $query = SalesRow::query()->where('import_batch_id', $importBatch->id);

        if ($bucket !== null) {
            $query->where('source_bucket', $bucket);
        }

        return $query->count();
    }
}
