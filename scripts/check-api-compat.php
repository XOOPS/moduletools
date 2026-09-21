<?php

declare(strict_types=1);

$packageRoot = dirname(__DIR__);
$baselineFile = $packageRoot . '/resources/gate/api-surface-baseline.json';
$currentFile  = $packageRoot . '/resources/gate/api-surface.json';
$allowlistFile = $packageRoot . '/resources/gate/api-surface-allowlist.json';

if (in_array('--accept-baseline', $argv, true)) {
    copy($currentFile, $baselineFile);
    fwrite(STDOUT, "Accepted current API surface as the compatibility baseline.\n");
    exit(0);
}

if (!is_file($baselineFile)) {
    fwrite(STDERR, "API compatibility baseline is missing.\n");
    exit(1);
}

$baseline = json_decode((string) file_get_contents($baselineFile), true, flags: JSON_THROW_ON_ERROR);
$current  = json_decode((string) file_get_contents($currentFile), true, flags: JSON_THROW_ON_ERROR);
$allowlist = is_file($allowlistFile)
    ? json_decode((string) file_get_contents($allowlistFile), true, flags: JSON_THROW_ON_ERROR)
    : ['allowed_changes' => []];
$allowed = array_fill_keys($allowlist['allowed_changes'] ?? [], true);
$changes = [];

compareApi($baseline['symbols'], $current['symbols'], 'symbols', $changes);
compareApi($baseline['global_functions'], $current['global_functions'], 'global_functions', $changes);
compareApi($baseline['alias_rules'], $current['alias_rules'], 'alias_rules', $changes);
$unexpected = array_values(array_filter($changes, static fn (string $change): bool => !isset($allowed[$change])));

if ([] !== $unexpected) {
    fwrite(STDERR, "Unexpected public API changes:\n- " . implode("\n- ", $unexpected) . "\n");
    exit(1);
}

fwrite(STDOUT, sprintf("API compatibility gate passed (%d allowlisted changes).\n", count($changes)));

/** @param mixed $before @param mixed $after @param list<string> $changes */
function compareApi(mixed $before, mixed $after, string $path, array &$changes): void
{
    if (gettype($before) !== gettype($after)) {
        $changes[] = $path . ':type-changed';
        return;
    }
    if (!is_array($before)) {
        if ($before !== $after) {
            $changes[] = $path . ':value-changed';
        }
        return;
    }
    foreach ($before as $key => $value) {
        $child = $path . '/' . (string)$key;
        if (!array_key_exists($key, $after)) {
            $changes[] = $child . ':removed';
            continue;
        }
        compareApi($value, $after[$key], $child, $changes);
    }
    foreach ($after as $key => $_value) {
        if (!array_key_exists($key, $before)) {
            $changes[] = $path . '/' . (string)$key . ':added';
        }
    }
}
