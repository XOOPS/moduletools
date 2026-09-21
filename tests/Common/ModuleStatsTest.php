<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Tests\Common;

use PHPUnit\Framework\TestCase;
use Xoops\ModuleTools\Common\SysUtility;

final class ModuleStatsTest extends TestCase
{
    public function testCompatibilityFacadeExposesConfiguredModuleStats(): void
    {
        $configurator = new class {
            /** @var array<string, int> */
            public array $moduleStats = [
                'totalitems'     => 12,
                'totalpublished' => 9,
            ];
        };

        self::assertTrue(method_exists(SysUtility::class, 'getModuleStats'));
        self::assertSame(
            ['existing' => 1, 'totalitems' => 12, 'totalpublished' => 9],
            SysUtility::getModuleStats($configurator, ['existing' => 1]),
        );
    }
}
