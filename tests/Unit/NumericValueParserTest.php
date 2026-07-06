<?php

namespace Tests\Unit;

use App\Domain\WeeklyAnalysis\Imports\Support\NumericValueParser;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NumericValueParserTest extends TestCase
{
    #[DataProvider('numericValueProvider')]
    public function test_parses_accounting_and_plain_numeric_values(mixed $input, ?float $expected): void
    {
        $this->assertSame($expected, NumericValueParser::parse($input));
    }

    public static function numericValueProvider(): array
    {
        return [
            '(75.00)' => ['(75.00)', -75.0],
            '(1,092.00)' => ['(1,092.00)', -1092.0],
            '75.00' => ['75.00', 75.0],
            '-75.00' => ['-75.00', -75.0],
            '75.00-' => ['75.00-', -75.0],
            '$1,234.56' => ['$1,234.56', 1234.56],
            '(-260)' => ['(-260)', -260.0],
            'n/a' => ['n/a', null],
            '-' => ['-', 0.0],
            '' => ['', null],
            null => [null, null],
            'float negative' => [-42.5, -42.5],
        ];
    }
}
