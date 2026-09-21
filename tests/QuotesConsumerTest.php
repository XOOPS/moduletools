<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Tests;

use PHPUnit\Framework\TestCase;

final class QuotesConsumerTest extends TestCase
{
    private static string $quotesPath;

    public static function setUpBeforeClass(): void
    {
        self::$quotesPath = dirname(__DIR__, 5) . '/modules/quotes';
        if (!is_dir(self::$quotesPath)) {
            self::markTestSkipped('quotes module not present (needs a XOOPS tree five levels up)');
        }
        require_once self::$quotesPath . '/bootstrap.php';
    }

    public function testQuotesLoadsAgainstCoreLibraryWithoutMtoolsModule(): void
    {
        self::assertSame('', \quotes_moduletools_dependency_error());
        self::assertTrue(class_exists(\XoopsModules\Quotes\Utility::class));
        self::assertTrue(method_exists(\XoopsModules\Quotes\Utility::class, 'getServerStats'));
    }

    public function testQuotesManifestDoesNotDeclareMtoolsModuleDependency(): void
    {
        $manifest = file_get_contents(self::$quotesPath . '/xoops_version.php');

        self::assertIsString($manifest);
        self::assertStringNotContainsString("'min_modules'", $manifest);
        self::assertStringNotContainsString('/modules/mtools/bootstrap.php', $manifest);
    }

    public function testQuotesProductionCodeUsesModernNamespace(): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(self::$quotesPath, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || 'php' !== $file->getExtension()) {
                continue;
            }

            $source = file_get_contents($file->getPathname());
            self::assertIsString($source);
            self::assertStringNotContainsString('XoopsModules\\Mtools', $source, $file->getPathname());
        }
    }
}
