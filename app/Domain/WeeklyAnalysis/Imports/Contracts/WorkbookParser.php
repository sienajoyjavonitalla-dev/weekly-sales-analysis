<?php

namespace App\Domain\WeeklyAnalysis\Imports\Contracts;

use App\Domain\WeeklyAnalysis\Imports\Data\ParsedWorkbook;
use App\Domain\WeeklyAnalysis\Imports\Enums\WorkbookType;

interface WorkbookParser
{
    public function type(): WorkbookType;

    public function parse(string $path): ParsedWorkbook;
}
