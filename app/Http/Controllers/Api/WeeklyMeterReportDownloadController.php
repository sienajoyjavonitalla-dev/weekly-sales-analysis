<?php

namespace App\Http\Controllers\Api;

use App\Domain\WeeklyAnalysis\Exports\Services\WeeklyMeterReportExporter;
use App\Domain\WeeklyAnalysis\Security\Services\AuditLogger;
use App\Models\ImportBatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WeeklyMeterReportDownloadController
{
    public function __invoke(
        Request $request,
        WeeklyMeterReportExporter $exporter,
        AuditLogger $auditLogger,
    ): StreamedResponse|JsonResponse {
        Gate::authorize('viewAny', ImportBatch::class);

        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        $year = (int) $validated['year'];
        $spreadsheet = $exporter->exportYear($year);

        if ($spreadsheet === null) {
            return response()->json(['message' => "There is no Meter Report data for {$year} yet."], 422);
        }

        $auditLogger->log('weekly_meter_report.year_downloaded', $request, null, null, ['year' => $year]);

        $fileName = "{$year}-weekly-meter-report.xlsx";

        return response()->streamDownload(function () use ($spreadsheet): void {
            IOFactory::createWriter($spreadsheet, 'Xlsx')->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
