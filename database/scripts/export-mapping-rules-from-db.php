<?php

use App\Models\MappingRule;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$destination = $argv[1] ?? dirname(__DIR__).'/data/seeders/mapping_rules.json';

$rules = MappingRule::query()
    ->with('productCategory:id,code')
    ->orderBy('id')
    ->get();

$rows = $rules->map(static function (MappingRule $rule): array {
    return [
        'name' => $rule->name,
        'category_code' => $rule->productCategory?->code,
        'source_type' => $rule->source_type,
        'match_field' => $rule->match_field,
        'match_operator' => $rule->match_operator,
        'pattern' => $rule->pattern,
        'target_bucket' => $rule->target_bucket,
        'priority' => (int) $rule->priority,
        'is_active' => (bool) $rule->is_active,
        'metadata' => $rule->metadata,
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

$withCategory = count(array_filter($rows, static fn (array $row): bool => $row['category_code'] !== null));

echo 'Wrote '.count($rows)." mapping rules to {$destination}\n";
echo "Rules with category_code from FK: {$withCategory}\n";
