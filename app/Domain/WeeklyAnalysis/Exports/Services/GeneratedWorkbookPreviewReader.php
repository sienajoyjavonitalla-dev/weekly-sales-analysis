<?php

namespace App\Domain\WeeklyAnalysis\Exports\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Serializes a generated workbook (cells, merges, fills, fonts, widths) into
 * JSON so the UI can render each sheet exactly as it appears in the file.
 */
class GeneratedWorkbookPreviewReader
{
    private const LAST_COLUMN_INDEX = 12; // A..L

    /**
     * @return array<int, array<string, mixed>>
     */
    public function read(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $spreadsheet = $reader->load($path);

        $sheets = [];

        foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
            $sheets[] = $this->readSheet($worksheet);
        }

        $spreadsheet->disconnectWorksheets();

        return $sheets;
    }

    /**
     * @return array<string, mixed>
     */
    private function readSheet(Worksheet $sheet): array
    {
        $lastRow = max(1, $sheet->getHighestDataRow());
        $merges = $this->mergeMap($sheet);

        $styles = [];
        $styleIndex = [];
        $rows = [];

        for ($rowNumber = 1; $rowNumber <= $lastRow; $rowNumber++) {
            $cells = [];

            for ($columnIndex = 1; $columnIndex <= self::LAST_COLUMN_INDEX; $columnIndex++) {
                $coordinate = Coordinate::stringFromColumnIndex($columnIndex).$rowNumber;
                $merge = $merges[$coordinate] ?? null;

                if ($merge !== null && ! $merge['origin']) {
                    continue;
                }

                $cell = $sheet->getCell($coordinate);
                $rawValue = $cell->getValue();
                $isNumeric = is_int($rawValue) || is_float($rawValue);
                $style = $this->cellStyle($sheet, $coordinate, $isNumeric);
                $styleKey = json_encode($style);

                if (! isset($styleIndex[$styleKey])) {
                    $styleIndex[$styleKey] = count($styles);
                    $styles[] = $style;
                }

                $cells[] = array_filter([
                    'v' => $rawValue === null ? '' : (string) $cell->getFormattedValue(),
                    's' => $styleIndex[$styleKey],
                    'colspan' => $merge['colspan'] ?? null,
                    'rowspan' => $merge['rowspan'] ?? null,
                ], static fn (mixed $value): bool => $value !== null);
            }

            $rows[] = ['n' => $rowNumber, 'cells' => $cells];
        }

        $widths = [];

        for ($columnIndex = 1; $columnIndex <= self::LAST_COLUMN_INDEX; $columnIndex++) {
            $letter = Coordinate::stringFromColumnIndex($columnIndex);
            $dimension = $sheet->getColumnDimension($letter);
            $widths[] = $dimension->getWidth() > 0 ? (float) $dimension->getWidth() : 8.43;
        }

        return [
            'title' => $sheet->getTitle(),
            'column_letters' => array_map(
                static fn (int $index): string => Coordinate::stringFromColumnIndex($index),
                range(1, self::LAST_COLUMN_INDEX),
            ),
            'column_widths' => $widths,
            'freeze_pane' => $sheet->getFreezePane(),
            'styles' => $styles,
            'rows' => $rows,
        ];
    }

    /**
     * @return array<string, array{origin: bool, colspan?: int, rowspan?: int}>
     */
    private function mergeMap(Worksheet $sheet): array
    {
        $map = [];

        foreach ($sheet->getMergeCells() as $range) {
            [$start, $end] = Coordinate::rangeBoundaries($range);
            [$startColumn, $startRow] = $start;
            [$endColumn, $endRow] = $end;

            for ($column = $startColumn; $column <= $endColumn; $column++) {
                for ($row = $startRow; $row <= $endRow; $row++) {
                    $coordinate = Coordinate::stringFromColumnIndex($column).$row;
                    $isOrigin = $column === (int) $startColumn && $row === (int) $startRow;

                    $map[$coordinate] = $isOrigin
                        ? [
                            'origin' => true,
                            'colspan' => min((int) $endColumn, self::LAST_COLUMN_INDEX) - (int) $startColumn + 1,
                            'rowspan' => (int) $endRow - (int) $startRow + 1,
                        ]
                        : ['origin' => false];
                }
            }
        }

        foreach ($map as $coordinate => $entry) {
            if (($entry['colspan'] ?? 1) === 1) {
                unset($map[$coordinate]['colspan']);
            }

            if (($entry['rowspan'] ?? 1) === 1) {
                unset($map[$coordinate]['rowspan']);
            }
        }

        return $map;
    }

    /**
     * @return array<string, mixed>
     */
    private function cellStyle(Worksheet $sheet, string $coordinate, bool $isNumeric): array
    {
        $style = $sheet->getStyle($coordinate);
        $font = $style->getFont();
        $fill = $style->getFill();

        $result = [];

        if ($fill->getFillType() === Fill::FILL_SOLID) {
            $result['fill'] = strtoupper($fill->getStartColor()->getRGB());
        }

        if ($font->getBold()) {
            $result['bold'] = true;
        }

        if ($font->getItalic()) {
            $result['italic'] = true;
        }

        if ((float) $font->getSize() !== 11.0) {
            $result['size'] = (float) $font->getSize();
        }

        $horizontal = $style->getAlignment()->getHorizontal();
        $result['align'] = match ($horizontal) {
            Alignment::HORIZONTAL_CENTER => 'center',
            Alignment::HORIZONTAL_RIGHT => 'right',
            Alignment::HORIZONTAL_LEFT => 'left',
            default => $isNumeric ? 'right' : 'left',
        };

        return $result;
    }
}
