<?php

$path = dirname(__DIR__).'/data/seeders/product_categories.json';
$rows = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

foreach ($rows as &$row) {
    if (! array_key_exists('quantity_multiplier', $row)) {
        $inserted = [];
        foreach ($row as $key => $value) {
            $inserted[$key] = $value;
            if ($key === 'sort_order') {
                $inserted['quantity_multiplier'] = 1;
            }
        }
        $row = $inserted;
    }
}
unset($row);

file_put_contents(
    $path,
    json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE)."\n",
);

echo 'Updated '.count($rows)." categories\n";
