<?php

namespace App\Domain\WeeklyAnalysis\Imports\Data;

use App\Domain\WeeklyAnalysis\Imports\Enums\WorkbookType;

class WorkbookSetValidationResult
{
    /**
     * @param  array<string, ParsedWorkbook>  $workbooks
     * @param  array<int, WorkbookValidationIssue>  $issues
     */
    public function __construct(
        public readonly array $workbooks,
        public readonly array $issues = [],
    ) {
    }

    public function isValid(): bool
    {
        if ($this->issues !== []) {
            return false;
        }

        foreach ($this->workbooks as $workbook) {
            if (! $workbook->isValid()) {
                return false;
            }
        }

        return true;
    }

    public function workbook(WorkbookType $type): ?ParsedWorkbook
    {
        return $this->workbooks[$type->value] ?? null;
    }

    public function toArray(): array
    {
        return [
            'valid' => $this->isValid(),
            'issues' => array_map(
                static fn (WorkbookValidationIssue $issue): array => $issue->toArray(),
                $this->issues,
            ),
            'workbooks' => array_map(
                static fn (ParsedWorkbook $workbook): array => $workbook->toArray(),
                $this->workbooks,
            ),
        ];
    }
}
