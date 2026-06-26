<?php

namespace App\Domain\WeeklyAnalysis\Exports\Services;

class SpreadsheetValueSanitizer
{
    public function sanitize(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $value = trim($value);

        if ($value === '') {
            return $value;
        }

        return in_array($value[0], ['=', '+', '-', '@'], true) ? "'{$value}" : $value;
    }
}
