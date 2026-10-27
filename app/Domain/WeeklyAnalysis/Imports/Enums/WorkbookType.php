<?php

namespace App\Domain\WeeklyAnalysis\Imports\Enums;

enum WorkbookType: string
{
    case SalesAnalysis = 'sales_analysis';
    case AmazonFees = 'amazon_fees';
    case WalmartFees = 'walmart_fees';
    case WeeklyMeterReport = 'weekly_meter_report';
    case IncomeStatement = 'income_statement';
    case TotalSalesReport = 'total_sales_report';
    case OpenOrders = 'open_orders';
    case PtdOrders = 'ptd_orders';

    public function isClassifiable(): bool
    {
        return in_array($this, [self::SalesAnalysis, self::AmazonFees, self::WalmartFees], true);
    }

    public function isMarketplaceFees(): bool
    {
        return in_array($this, [self::AmazonFees, self::WalmartFees], true);
    }

    public function marketplace(): ?string
    {
        return match ($this) {
            self::AmazonFees => 'amazon',
            self::WalmartFees => 'walmart',
            default => null,
        };
    }

    /**
     * @return list<self>
     */
    public static function uploadTypes(): array
    {
        return [
            self::SalesAnalysis,
            self::AmazonFees,
            self::WalmartFees,
        ];
    }

    /**
     * @return list<string>
     */
    public static function classifiableValues(): array
    {
        return [
            self::SalesAnalysis->value,
            self::AmazonFees->value,
            self::WalmartFees->value,
        ];
    }
}
