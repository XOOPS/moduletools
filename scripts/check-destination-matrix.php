<?php

declare(strict_types=1);

$packageRoot = dirname(__DIR__);
$matrix = json_decode((string) file_get_contents($packageRoot . '/resources/gate/destination-matrix.json'), true, flags: JSON_THROW_ON_ERROR);
$valid = [
    'disposition'  => ['retain-compat', 'promote', 'migrate-to-target', 'blocked-on-upstream', 'remove'],
    'coupling'     => ['pure-php', 'xoops-runtime', 'xoopsobject-handler', 'request-global', 'presentation-html'],
    'target_status' => ['stable', 'provisional', 'experimental', 'missing', 'not-applicable'],
    'availability' => ['delegate-now', 'migrate-on-4.0', 'not-applicable'],
    'migration'    => ['delegation', 'adapter', 'rector-rule', 'manual-recipe', 'none-yet'],
];
$errors = [];
$seen = [];
foreach ($matrix['records'] as $record) {
    $seen[$record['symbol']] = true;
    foreach (['disposition', 'target_status', 'availability'] as $field) {
        if (!in_array($record[$field], $valid[$field], true)) {
            $errors[] = $record['symbol'] . ': invalid ' . $field;
        }
    }
    foreach (['coupling', 'migration'] as $field) {
        if ([] === $record[$field] || array_diff($record[$field], $valid[$field])) {
            $errors[] = $record['symbol'] . ': invalid ' . $field;
        }
    }
    if (str_contains($record['symbol'], '\\Transitional\\')) {
        foreach (['upstream_issue', 'introduced_version', 'not_before_release', 'next_review_release'] as $field) {
            if ('' === $record[$field]) {
                $errors[] = $record['symbol'] . ': transitional record missing ' . $field;
            }
        }
    }
    if ('delegate-now' === $record['availability'] && 'none' === $record['target_symbol']) {
        $errors[] = $record['symbol'] . ': delegate-now record has no exact target';
    }
}

$surface = json_decode((string) file_get_contents($packageRoot . '/resources/gate/api-surface.json'), true, flags: JSON_THROW_ON_ERROR);
foreach ($surface['symbols'] as $symbol => $definition) {
    $expected = [$symbol];
    foreach (array_keys($definition['constants']) as $name) {
        $expected[] = $symbol . '::' . $name;
    }
    foreach (array_keys($definition['properties']) as $name) {
        $expected[] = $symbol . '::$' . $name;
    }
    foreach (array_keys($definition['methods']) as $name) {
        $expected[] = $symbol . '::' . $name . '()';
    }
    foreach ($expected as $name) {
        if (!isset($seen[$name])) {
            $errors[] = 'Missing matrix record: ' . $name;
        }
    }
}

if ([] !== $errors) {
    fwrite(STDERR, implode(PHP_EOL, $errors) . PHP_EOL);
    exit(1);
}

$transitional = array_filter($matrix['records'], static fn (array $r): bool => str_contains($r['symbol'], '\\Transitional\\'));
fwrite(STDOUT, sprintf("Destination matrix valid; %d unresolved transitional contracts.\n", count($transitional)));
