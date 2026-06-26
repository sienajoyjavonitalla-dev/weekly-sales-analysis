<?php

namespace App\Domain\WeeklyAnalysis\Imports\Enums;

enum WorkbookType: string
{
    case SalesAnalysis = 'sales_analysis';
    case IncomeStatement = 'income_statement';
    case TotalSalesReport = 'total_sales_report';
    case WeeklyMeterReport = 'weekly_meter_report';
    case OpenOrders = 'open_orders';
    case PtdOrders = 'ptd_orders';
}
