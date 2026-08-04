<?php

use App\Models\MappingRule;
use App\Models\ProductCategory;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$destination = $argv[1] ?? dirname(__DIR__).'/data/seeders/product_categories.json';

$categories = ProductCategory::query()
    ->orderBy('id')
    ->get([
        'id',
        'code',
        'name',
        'report_family',
        'sales_analysis_bucket',
        'total_sales_row_label',
        'weekly_meter_row_label',
        'sort_order',
        'is_active',
        'metadata',
    ]);

$referencedIds = MappingRule::query()
    ->whereNotNull('product_category_id')
    ->distinct()
    ->pluck('product_category_id')
    ->all();

$missingFkIds = array_values(array_diff($referencedIds, $categories->pluck('id')->all()));

$rows = $categories->map(static function (ProductCategory $category): array {
    return [
        'code' => $category->code,
        'name' => $category->name,
        'report_family' => $category->report_family,
        'sales_analysis_bucket' => $category->sales_analysis_bucket,
        'total_sales_row_label' => $category->total_sales_row_label,
        'weekly_meter_row_label' => $category->weekly_meter_row_label,
        'sort_order' => (int) $category->sort_order,
        'is_active' => (bool) $category->is_active,
        'metadata' => $category->metadata,
    ];
})->values()->all();

$directory = dirname($destination);
if (! is_dir($directory)) {
    mkdir($directory, 0775, true);
}

$json = json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
if ($json === false) {
    fwrite(STDERR, 'JSON encode failed: '.json_last_error_msg()."\n");
    exit(1);
}

file_put_contents($destination, $json."\n");

echo 'Wrote '.count($rows)." categories to {$destination}\n";
echo 'Mapping rules referencing categories: '.count($referencedIds)."\n";

if ($missingFkIds !== []) {
    echo 'WARNING: mapping_rules point to missing category ids: '.implode(', ', $missingFkIds)."\n";
    exit(2);
}
