<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Tests;

use PHPUnit\Framework\TestCase;

final class ExtcalConsumerTest extends TestCase
{
    private static string $modulePath;

    public static function setUpBeforeClass(): void
    {
        self::$modulePath = dirname(__DIR__, 5) . '/modules/extcal';
        if (!is_dir(self::$modulePath)) {
            self::markTestSkipped('extcal module not present (needs a XOOPS tree five levels up)');
        }
    }

    public function testExtcalUsesCoreModuleToolsInsteadOfModuleLocalCommonCopies(): void
    {
        self::assertDirectoryDoesNotExist(self::$modulePath . '/class/Common');

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(self::$modulePath, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || 'php' !== $file->getExtension()) {
                continue;
            }
            if (str_contains(str_replace('\\', '/', $file->getPathname()), '/tests/')) {
                continue;
            }

            $source = (string) file_get_contents($file->getPathname());
            self::assertStringNotContainsString('XoopsModules\\Extcal\\Common', $source, $file->getPathname());
        }

        self::assertStringContainsString(
            'use Xoops\\ModuleTools\\Common\\SysUtility;',
            (string) file_get_contents(self::$modulePath . '/class/Utility.php'),
        );
        self::assertStringContainsString(
            'use Xoops\\ModuleTools\\Common\\Configurator;',
            (string) file_get_contents(self::$modulePath . '/include/oninstall.php'),
        );
        $updateSource = (string) file_get_contents(self::$modulePath . '/include/onupdate.php');
        self::assertStringContainsString('use Xoops\\ModuleTools\\Common\\{', $updateSource);
        self::assertStringContainsString('    Migrate', $updateSource);
    }
}
