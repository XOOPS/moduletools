<?php

declare(strict_types=1);

$packageRoot = dirname(__DIR__);
$checkOnly   = in_array('--check', $argv, true);
$surface     = json_decode((string) file_get_contents($packageRoot . '/resources/gate/api-surface.json'), true, flags: JSON_THROW_ON_ERROR);
// The consumer audit is a private, site-specific scan; without it every symbol counts as unused.
$auditFile   = $packageRoot . '/docs/internal/consumer-audit.json';
$audit       = is_file($auditFile) ? json_decode((string) file_get_contents($auditFile), true, flags: JSON_THROW_ON_ERROR) : ['modules' => []];
$decisions   = require $packageRoot . '/resources/migration/destination-decisions.php';
$matrix      = ['schema_version' => 1, 'records' => []];

foreach ($surface['symbols'] as $fqcn => $symbol) {
    $relative = str_replace('Xoops\\ModuleTools\\', '', $fqcn);
    if (!isset($decisions[$relative])) {
        throw new RuntimeException('Missing destination decision for ' . $fqcn);
    }
    $decision = $decisions[$relative];
    addRecord($matrix['records'], $fqcn, 'type', $decision, consumerUsage($fqcn, $audit));
    foreach (array_keys($symbol['constants']) as $constant) {
        addRecord($matrix['records'], $fqcn . '::' . $constant, 'constant', $decision, consumerUsage($fqcn . '::' . $constant, $audit));
    }
    foreach (array_keys($symbol['properties']) as $property) {
        addRecord($matrix['records'], $fqcn . '::$' . $property, 'property', $decision, consumerUsage($fqcn, $audit));
    }
    foreach (array_keys($symbol['methods']) as $method) {
        $memberKey      = $relative . '::' . $method . '()';
        $memberDecision = $decisions[$memberKey] ?? $decision;
        addRecord($matrix['records'], $fqcn . '::' . $method . '()', 'method', $memberDecision, consumerUsage($fqcn . '::' . $method, $audit));
    }
}

usort($matrix['records'], static fn (array $a, array $b): int => $a['symbol'] <=> $b['symbol']);
$json = json_encode($matrix, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
$markdown = renderMatrix($matrix);
$backlog = renderBacklog($matrix);
$targets = [
    $packageRoot . '/resources/gate/destination-matrix.json' => $json,
    $packageRoot . '/resources/gate/destination-matrix.md'   => $markdown,
    $packageRoot . '/resources/gate/promotion-backlog.md'    => $backlog,
];

if ($checkOnly) {
    foreach ($targets as $file => $contents) {
        if (!is_file($file) || file_get_contents($file) !== $contents) {
            fwrite(STDERR, "Destination artifacts are stale. Run composer matrix:generate.\n");
            exit(1);
        }
    }
    fwrite(STDOUT, "Destination matrix is current.\n");
    exit(0);
}
foreach ($targets as $file => $contents) {
    file_put_contents($file, $contents);
}
fwrite(STDOUT, sprintf("Wrote %d destination records.\n", count($matrix['records'])));

/** @param list<array<string, mixed>> $records @param array<string, mixed> $decision @param array<string, mixed> $usage */
function addRecord(array &$records, string $symbol, string $kind, array $decision, array $usage): void
{
    $records[] = ['symbol' => $symbol, 'kind' => $kind] + $decision + ['consumers' => $usage];
}

/** @param array<string, mixed> $audit @return array{usage_count:int, modules:list<string>} */
function consumerUsage(string $symbol, array $audit): array
{
    $lookup = str_replace('()', '', $symbol);
    $modules = [];
    $count = 0;
    foreach ($audit['modules'] as $module => $record) {
        $hits = ($record['modern_symbols'][$lookup] ?? 0) + ($record['legacy_symbols'][str_replace(
            'Xoops\\ModuleTools\\',
            'XoopsModules\\Mtools\\',
            $lookup,
        )] ?? 0) + ($record['static_calls'][$lookup] ?? 0);
        if (0 < $hits) {
            $modules[] = $module;
            $count += $hits;
        }
    }
    return ['usage_count' => $count, 'modules' => $modules];
}

/** @param array<string, mixed> $matrix */
function renderMatrix(array $matrix): string
{
    $lines = [
        '# ModuleTools destination matrix', '',
        '> Generated from the API surface, consumer audit, and `resources/migration/destination-decisions.php`.', '',
        '| Symbol | Kind | Disposition | Availability | Target | Status | Uses | Parity |',
        '|---|---|---|---|---|---|---:|---|',
    ];
    foreach ($matrix['records'] as $record) {
        $target = 'none' === $record['target_symbol'] ? '—' : $record['target_symbol'];
        $lines[] = sprintf(
            '| `%s` | %s | %s%s | %s | `%s` | %s | %d | `%s` |',
            $record['symbol'], $record['kind'], $record['disposition'],
            '' === $record['qualifier'] ? '' : ' (' . $record['qualifier'] . ')',
            $record['availability'], $target, $record['target_status'],
            $record['consumers']['usage_count'], $record['parity_test'],
        );
    }
    return implode(PHP_EOL, $lines) . PHP_EOL;
}

/** @param array<string, mixed> $matrix */
function renderBacklog(array $matrix): string
{
    $lines = ['# ModuleTools promotion backlog', '', '> Generated from unresolved type-level destination records.', ''];
    foreach ($matrix['records'] as $record) {
        if ('type' !== $record['kind'] || ('blocked-on-upstream' !== $record['disposition']
            && 'missing' !== $record['parity_test'])) {
            continue;
        }
        $lines[] = '## ' . $record['symbol'];
        $lines[] = '';
        $lines[] = '- Target: `' . $record['target_package'] . '` / `' . $record['target_symbol'] . '` (' . $record['target_status'] . ').';
        $lines[] = '- Blocker: ' . $record['notes'];
        $lines[] = '- Required evidence: behavioral parity corpus plus an explicit stable owner.';
        $lines[] = '';
    }
    return implode(PHP_EOL, $lines) . PHP_EOL;
}
