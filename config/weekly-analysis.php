<?php

return [
    'uploads' => [
        'max_file_size_kb' => env('WEEKLY_ANALYSIS_MAX_UPLOAD_KB', 20480),
        'allowed_extensions' => ['xlsx'],
        'allowed_mime_types' => [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/zip',
        ],
        'required_file_types' => [
            'sales_analysis',
            'income_statement',
            'total_sales_report',
            'weekly_meter_report',
            'open_orders',
            'ptd_orders',
        ],
    ],
    'sales_analysis_buckets' => [
        'rhp',
        'parts_tsd',
        'state',
    ],
    'exclusive_sales_analysis_buckets' => [
        'rhp',
        'parts_tsd',
    ],
];
