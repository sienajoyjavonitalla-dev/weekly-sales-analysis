<?php

namespace App\Domain\WeeklyAnalysis\Imports\Support;

class NumericValueParser
{
    public static function parse(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $value = trim((string) $value);

        if ($value === '' || strtolower($value) === 'n/a') {
            return null;
        }

        $negative = false;

        if (preg_match('/^\((.+)\)$/', $value, $matches) === 1) {
            $value = trim($matches[1]);
            $negative = true;
        } elseif (str_ends_with($value, '-')) {
            $value = rtrim($value, '-');
            $negative = true;
        } elseif (str_starts_with($value, '-')) {
            $value = ltrim($value, '-');
            $negative = true;
        }

        $normalized = str_replace([',', '$', '%', ' '], '', $value);

        if ($normalized === '') {
            return 0.0;
        }

        if (! is_numeric($normalized)) {
            return null;
        }

        $number = (float) $normalized;

        return $negative ? -abs($number) : $number;
    }
}
