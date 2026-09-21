<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Tests;

use PHPUnit\Framework\TestCase;

final class ApiSurfaceTest extends TestCase
{
    public function testEveryRecordedSymbolCanBeAutoloadedInTheControlledHarness(): void
    {
        $surface = json_decode(
            (string) file_get_contents(dirname(__DIR__) . '/resources/gate/api-surface.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        foreach (array_keys($surface['symbols']) as $symbol) {
            $loaded = class_exists($symbol) || interface_exists($symbol) || trait_exists($symbol);
            self::assertTrue($loaded, $symbol);
        }
    }

    public function testLegacyAliasRuleResolvesEveryRecordedSymbol(): void
    {
        $surface = json_decode(
            (string) file_get_contents(dirname(__DIR__) . '/resources/gate/api-surface.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        foreach (array_keys($surface['symbols']) as $symbol) {
            $legacy = str_replace('Xoops\\ModuleTools\\', 'XoopsModules\\Mtools\\', $symbol);
            $loaded = class_exists($legacy) || interface_exists($legacy) || trait_exists($legacy);
            self::assertTrue($loaded, $legacy);
        }
    }

    public function testLegacyTraitAliasIsUsable(): void
    {
        // class_alias() has aliased traits since PHP 5.4.2 (php.net bug #61442).
        self::assertTrue(trait_exists('XoopsModules\\Mtools\\Common\\ServerStats'));
        $consumer = new class {
            use \XoopsModules\Mtools\Common\ServerStats;
        };
        self::assertTrue(method_exists($consumer, 'getServerStats'));
    }
}
