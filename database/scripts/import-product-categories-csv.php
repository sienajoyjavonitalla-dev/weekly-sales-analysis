<?php

$source = $argv[1] ?? null;
$destination = $argv[2] ?? dirname(__DIR__).'/data/seeders/product_categories.json';

if ($source === null || ! is_file($source)) {
    fwrite(STDERR, "Usage: php import-product-categories-csv.php <source.csv> [destination.json]\n");
    exit(1);
}

$directory = dirname($destination);
if (! is_dir($directory)) {
    mkdir($directory, 0775, true);
}

$raw = file_get_contents($source);
if ($raw === false) {
    fwrite(STDERR, "Unable to read CSV: {$source}\n");
    exit(1);
}

// Workbench CSV exports are often Windows-1252 / ISO-8859-1.
if (! mb_check_encoding($raw, 'UTF-8')) {
    $raw = mb_convert_encoding($raw, 'UTF-8', 'Windows-1252');
}

$handle = fopen('php://memory', 'r+');
fwrite($handle, $raw);
rewind($handle);

$header = fgetcsv($handle, 0, ';');
if ($header === false) {
    fwrite(STDERR, "CSV header missing.\n");
    exit(1);
}

$rows = [];

while (($data = fgetcsv($handle, 0, ';')) !== false) {
    if (count($data) < count($header)) {
        continue;
    }

    $row = array_combine($header, $data);
    if ($row === false) {
        continue;
    }

    $metadataRaw = trim((string) ($row['metadata'] ?? ''));
    $metadata = $metadataRaw === '' ? null : json_decode($metadataRaw, true);

    if ($metadataRaw !== '' && json_last_error() !== JSON_ERROR_NONE) {
        fwrite(STDERR, "Invalid metadata JSON for code {$row['code']}: ".json_last_error_msg()."\n");
        exit(1);
    }

    $rows[] = [
        'code' => $row['code'],
        'name' => $row['name'],
        'report_family' => $row['report_family'],
        'sales_analysis_bucket' => $row['sales_analysis_bucket'] !== '' ? $row['sales_analysis_bucket'] : null,
        'total_sales_row_label' => $row['total_sales_row_label'] !== '' ? $row['total_sales_row_label'] : null,
        'weekly_meter_row_label' => $row['weekly_meter_row_label'] !== '' ? $row['weekly_meter_row_label'] : null,
        'sort_order' => (int) $row['sort_order'],
        'is_active' => (bool) ((int) $row['is_active']),
        'metadata' => $metadata,
    ];
}

fclose($handle);

$json = json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
if ($json === false) {
    fwrite(STDERR, 'JSON encode failed: '.json_last_error_msg()."\n");
    exit(1);
}

file_put_contents($destination, $json."\n");

echo 'Wrote '.count($rows)." categories to {$destination}\n";
