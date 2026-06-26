<?php

namespace Tests\Unit;

use App\Domain\WeeklyAnalysis\Exports\Services\SpreadsheetValueSanitizer;
use PHPUnit\Framework\TestCase;

class SpreadsheetValueSanitizerTest extends TestCase
{
    public function test_it_prefixes_formula_like_values(): void
    {
        $sanitizer = new SpreadsheetValueSanitizer();

        $this->assertSame("'=SUM(A1:A2)", $sanitizer->sanitize('=SUM(A1:A2)'));
        $this->assertSame("'+HYPERLINK(\"http://example.com\")", $sanitizer->sanitize('+HYPERLINK("http://example.com")'));
        $this->assertSame("'-10", $sanitizer->sanitize('-10'));
        $this->assertSame("'@cmd", $sanitizer->sanitize('@cmd'));
    }

    public function test_it_keeps_safe_values_unchanged(): void
    {
        $sanitizer = new SpreadsheetValueSanitizer();

        $this->assertSame('Wagner', $sanitizer->sanitize('Wagner'));
        $this->assertSame(123.45, $sanitizer->sanitize(123.45));
    }
}
