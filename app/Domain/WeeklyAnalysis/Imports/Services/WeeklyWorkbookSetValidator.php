<?php

namespace App\Domain\WeeklyAnalysis\Imports\Services;

use App\Domain\WeeklyAnalysis\Imports\Contracts\WorkbookParser;
use App\Domain\WeeklyAnalysis\Imports\Data\WorkbookSetValidationResult;
use App\Domain\WeeklyAnalysis\Imports\Data\WorkbookValidationIssue;
use App\Domain\WeeklyAnalysis\Imports\Enums\WorkbookType;
use App\Domain\WeeklyAnalysis\Imports\Parsers\AmazonFeesWorkbookParser;
use App\Domain\WeeklyAnalysis\Imports\Parsers\SalesAnalysisWorkbookParser;
use App\Domain\WeeklyAnalysis\Imports\Parsers\WalmartFeesWorkbookParser;

class WeeklyWorkbookSetValidator
{
    /**
     * @var array<string, WorkbookParser>
     */
    private array $parsers;

    public function __construct()
    {
        $this->parsers = $this->buildParserMap([
            new SalesAnalysisWorkbookParser(),
            new AmazonFeesWorkbookParser(),
            new WalmartFeesWorkbookParser(),
        ]);
    }

    /**
     * @param  array<string, string>  $pathsByType
     */
    public function validate(array $pathsByType): WorkbookSetValidationResult
    {
        $workbooks = [];
        $issues = [];

        foreach (WorkbookType::uploadTypes() as $type) {
            $path = $pathsByType[$type->value] ?? null;

            if ($path === null || $path === '') {
                $issues[] = new WorkbookValidationIssue("Missing required {$type->value} workbook.");
                continue;
            }

            if (! is_file($path)) {
                $issues[] = new WorkbookValidationIssue("Workbook file does not exist for {$type->value}.");
                continue;
            }

            $workbooks[$type->value] = $this->parsers[$type->value]->parse($path);
        }

        return new WorkbookSetValidationResult($workbooks, $issues);
    }

    /**
     * @param  array<int, WorkbookParser>  $parsers
     * @return array<string, WorkbookParser>
     */
    private function buildParserMap(array $parsers): array
    {
        $map = [];

        foreach ($parsers as $parser) {
            $map[$parser->type()->value] = $parser;
        }

        return $map;
    }
}
