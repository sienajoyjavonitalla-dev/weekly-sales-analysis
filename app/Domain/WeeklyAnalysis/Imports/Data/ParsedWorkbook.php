<?php

namespace App\Domain\WeeklyAnalysis\Imports\Data;

use App\Domain\WeeklyAnalysis\Imports\Enums\WorkbookType;

class ParsedWorkbook
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, WorkbookValidationIssue>  $issues
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly WorkbookType $type,
        public readonly array $sheetNames,
        public readonly array $rows = [],
        public readonly array $issues = [],
        public readonly array $metadata = [],
    ) {
    }

    public function isValid(): bool
    {
        return $this->errors() === [];
    }

    /**
     * @return array<int, WorkbookValidationIssue>
     */
    public function errors(): array
    {
        return array_values(array_filter(
            $this->issues,
            static fn (WorkbookValidationIssue $issue): bool => $issue->severity === 'error',
        ));
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'sheet_names' => $this->sheetNames,
            'rows' => $this->rows,
            'issues' => array_map(
                static fn (WorkbookValidationIssue $issue): array => $issue->toArray(),
                $this->issues,
            ),
            'metadata' => $this->metadata,
            'valid' => $this->isValid(),
        ];
    }
}
