<?php

namespace App\Domain\WeeklyAnalysis\Imports\Parsers;

use App\Domain\WeeklyAnalysis\Imports\Enums\WorkbookType;

class AmazonFeesWorkbookParser extends MarketplaceFeesWorkbookParser
{
    public function type(): WorkbookType
    {
        return WorkbookType::AmazonFees;
    }
}
