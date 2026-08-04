<?php

$source = $argv[1] ?? dirname(__DIR__, 2).'/mapping_rules.sql';
$categoriesCsv = $argv[2] ?? null;
$destination = $argv[3] ?? dirname(__DIR__).'/data/seeders/mapping_rules.json';

if (! is_file($source)) {
    fwrite(STDERR, "Mapping rules SQL missing: {$source}\n");
    exit(1);
}

$directory = dirname($destination);
if (! is_dir($directory)) {
    mkdir($directory, 0775, true);
}

$categoryCodeById = [];

if ($categoriesCsv !== null && is_file($categoriesCsv)) {
    $raw = file_get_contents($categoriesCsv);
    if ($raw !== false && ! mb_check_encoding($raw, 'UTF-8')) {
        $raw = mb_convert_encoding($raw, 'UTF-8', 'Windows-1252');
    }

    $handle = fopen('php://memory', 'r+');
    fwrite($handle, (string) $raw);
    rewind($handle);
    $header = fgetcsv($handle, 0, ';');

    while ($header !== false && ($data = fgetcsv($handle, 0, ';')) !== false) {
        if (count($data) < count($header)) {
            continue;
        }

        $row = array_combine($header, $data);
        if ($row === false) {
            continue;
        }

        $categoryCodeById[(int) $row['id']] = $row['code'];
    }

    fclose($handle);
}

$sql = file_get_contents($source);
if ($sql === false) {
    fwrite(STDERR, "Unable to read SQL: {$source}\n");
    exit(1);
}

if (! preg_match('/INSERT INTO `mapping_rules` VALUES\s*(.+?);\s*\n/s', $sql, $matches)) {
    fwrite(STDERR, "INSERT statement not found in {$source}\n");
    exit(1);
}

$valuesSql = rtrim($matches[1], "; \r\n");
$length = strlen($valuesSql);
$rules = [];
$index = 0;

while ($index < $length) {
    while ($index < $length && ctype_space($valuesSql[$index])) {
        $index++;
    }

    if ($index >= $length) {
        break;
    }

    if ($valuesSql[$index] !== '(') {
        fwrite(STDERR, "Unexpected token at position {$index}\n");
        exit(1);
    }

    $index++;
    $fields = [];
    $field = '';
    $inString = false;

    while ($index < $length) {
        $char = $valuesSql[$index];

        if ($inString) {
            if ($char === '\\' && $index + 1 < $length) {
                $field .= $valuesSql[$index + 1];
                $index += 2;
                continue;
            }

            if ($char === "'") {
                if ($index + 1 < $length && $valuesSql[$index + 1] === "'") {
                    $field .= "'";
                    $index += 2;
                    continue;
                }

                $inString = false;
                $index++;
                continue;
            }

            $field .= $char;
            $index++;
            continue;
        }

        if ($char === "'") {
            $inString = true;
            $index++;
            continue;
        }

        if ($char === ',') {
            $fields[] = $field;
            $field = '';
            $index++;
            continue;
        }

        if ($char === ')') {
            $fields[] = $field;
            $index++;
            break;
        }

        $field .= $char;
        $index++;
    }

    while ($index < $length && ($valuesSql[$index] === ',' || ctype_space($valuesSql[$index]))) {
        $index++;
    }

    if (count($fields) < 12) {
        fwrite(STDERR, 'Unexpected field count: '.count($fields)."\n");
        exit(1);
    }

    $productCategoryId = strtoupper(trim($fields[1])) === 'NULL' ? null : (int) $fields[1];
    $metadataRaw = strtoupper(trim($fields[10])) === 'NULL' ? null : $fields[10];
    $metadata = null;

    if ($metadataRaw !== null) {
        $metadata = json_decode($metadataRaw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            fwrite(STDERR, 'Invalid metadata JSON for rule '.$fields[2].': '.json_last_error_msg()."\n");
            exit(1);
        }
    }

    $categoryCode = $metadata['category_code'] ?? null;
    if (($categoryCode === null || $categoryCode === '') && $productCategoryId !== null) {
        $categoryCode = $categoryCodeById[$productCategoryId] ?? null;
    }

    $rules[] = [
        'name' => $fields[2],
        'category_code' => $categoryCode ?: null,
        'source_type' => $fields[3],
        'match_field' => $fields[4],
        'match_operator' => $fields[5],
        'pattern' => $fields[6],
        'target_bucket' => strtoupper(trim($fields[7])) === 'NULL' ? null : $fields[7],
        'priority' => (int) $fields[8],
        'is_active' => (bool) ((int) $fields[9]),
        'metadata' => $metadata,
    ];
}

$json = json_encode($rules, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
if ($json === false) {
    fwrite(STDERR, 'JSON encode failed: '.json_last_error_msg()."\n");
    exit(1);
}

file_put_contents($destination, $json."\n");
echo 'Wrote '.count($rules)." mapping rules to {$destination}\n";
