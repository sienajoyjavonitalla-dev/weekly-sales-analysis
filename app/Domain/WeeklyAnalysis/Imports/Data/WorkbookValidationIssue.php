<?php

namespace App\Domain\WeeklyAnalysis\Imports\Data;

class WorkbookValidationIssue
{
    public function __construct(
        public readonly string $message,
        public readonly ?string $sheet = null,
        public readonly ?int $row = null,
        public readonly string $severity = 'error',
    ) {
    }

    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'sheet' => $this->sheet,
            'row' => $this->row,
            'severity' => $this->severity,
        ];
    }
}
