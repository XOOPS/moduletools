<?php

declare(strict_types=1);

// Runtime-only XOOPS constants and the package language catalog, so analysis
// resolves them instead of reporting each one as unknown.
foreach (['XOOPS_ROOT_PATH' => __DIR__, 'XOOPS_TRUST_PATH' => __DIR__, 'XOOPS_URL' => 'https://localhost', '_CHARSET' => 'utf-8'] as $name => $value) {
    if (!defined($name)) {
        define($name, $value);
    }
}
require_once __DIR__ . '/resources/language/english/common.php';
require_once __DIR__ . '/stubs/xoops-constants.php';
