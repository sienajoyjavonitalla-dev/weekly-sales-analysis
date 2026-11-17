<?php

namespace Tests;

use App\Domain\WeeklyAnalysis\Storage\WorkbookStorage;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function loadStoredSpreadsheet(string $storagePath): Spreadsheet
    {
        return app(WorkbookStorage::class)->withLocalCopy(
            $storagePath,
            fn (string $path): Spreadsheet => IOFactory::load($path),
        );
    }
}
