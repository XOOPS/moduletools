<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\ValueObject\PhpVersion;
use Xoops\Rector\Set\XoopsSetList;

return RectorConfig::configure()
    ->withCache(__DIR__ . '/.build/rector')
    ->withPaths([__DIR__ . '/src', __DIR__ . '/tests', __DIR__ . '/scripts'])
    // Reuse the PHPStan stubs so Rector resolves XoopsObject & co. (else parent:: calls get removed).
    ->withPHPStanConfigs([__DIR__ . '/phpstan.neon'])
    // PHP 8.2 is the floor (XOOPS 2.7 bundles this library): never upgrade past it.
    ->withPhpVersion(PhpVersion::PHP_82)
    ->withPhpSets(php82: true)
    // XOOPS-specific modernisation (DB query/exec split, Smarty PHP-API renames, …).
    ->withSets([XoopsSetList::XOOPS])
    ->withSkip([__DIR__ . '/tests/fixtures']);
