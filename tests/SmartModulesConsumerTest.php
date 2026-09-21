<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use XoopsModules\Smartfaq\DatabaseMigration;

final class SmartModulesConsumerTest extends TestCase
{
    /** @var array<string, string> */
    private static array $modulePaths;

    public static function setUpBeforeClass(): void
    {
        $modulesPath = dirname(__DIR__, 5) . '/modules';
        if (!is_dir($modulesPath . '/smartfaq')) {
            self::markTestSkipped('Smart modules not present (needs a XOOPS tree five levels up)');
        }
        self::$modulePaths = [
            'smartfaq'    => $modulesPath . '/smartfaq',
            'smartblocks' => $modulesPath . '/smartblocks',
            'smartmedia'  => $modulesPath . '/smartmedia',
            'smartpartner' => $modulesPath . '/smartpartner',
        ];

        require_once self::$modulePaths['smartfaq'] . '/bootstrap.php';
        require_once self::$modulePaths['smartblocks'] . '/bootstrap.php';
        require_once self::$modulePaths['smartmedia'] . '/bootstrap.php';
        require_once self::$modulePaths['smartpartner'] . '/bootstrap.php';
    }

    /** @return iterable<string, array{string, class-string, string}> */
    public static function modules(): iterable
    {
        yield 'SmartFAQ' => [
            'smartfaq',
            \XoopsModules\Smartfaq\Utility::class,
            'smartfaq_moduletools_dependency_error',
        ];
        yield 'SmartBlocks' => [
            'smartblocks',
            \XoopsModules\Smartblocks\Utility::class,
            'smartblocks_moduletools_dependency_error',
        ];
        yield 'SmartMedia' => [
            'smartmedia',
            \XoopsModules\Smartmedia\Utility::class,
            'smartmedia_moduletools_dependency_error',
        ];
        yield 'SmartPartner' => [
            'smartpartner',
            \XoopsModules\Smartpartner\Utility::class,
            'smartpartner_moduletools_dependency_error',
        ];
    }

    #[DataProvider('modules')]
    public function testConsumerLoadsDirectlyAgainstCoreLibrary(
        string $dirname,
        string $utilityClass,
        string $dependencyFunction,
    ): void {
        self::assertSame('', $dependencyFunction());
        self::assertTrue(class_exists($utilityClass));
        self::assertTrue(method_exists($utilityClass, 'getServerStats'));

        $manifest = file_get_contents(self::$modulePaths[$dirname] . '/xoops_version.php');
        self::assertIsString($manifest);
        self::assertStringNotContainsString("'min_modules'", $manifest);
        self::assertStringNotContainsString('/modules/mtools/bootstrap.php', $manifest);
        self::assertStringContainsString("'min_php'", $manifest);
        self::assertStringContainsString("'8.2'", $manifest);
    }

    #[DataProvider('modules')]
    public function testProductionCodeDoesNotUseRemovedFrameworks(
        string $dirname,
        string $utilityClass,
        string $dependencyFunction,
    ): void {
        unset($utilityClass, $dependencyFunction);
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(self::$modulePaths[$dirname], \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || 'php' !== $file->getExtension()) {
                continue;
            }
            if (str_contains(str_replace('\\', '/', $file->getPathname()), '/tests/')) {
                continue;
            }

            $source = file_get_contents($file->getPathname());
            self::assertIsString($source);
            $code = self::withoutComments($source);
            self::assertStringNotContainsString('SmartObject', $code, $file->getPathname());
            self::assertStringNotContainsString('XoopsModules\\Mtools', $code, $file->getPathname());
            self::assertStringNotContainsString('/modules/mtools/', $code, $file->getPathname());
        }
    }

    public function testSmartFaqMigrationRecognizesSupportedMysqlIntegerForms(): void
    {
        self::assertTrue(DatabaseMigration::isCompatibleIntegerDefinition(
            "int(11) unsigned NOT NULL DEFAULT '0'",
        ));
        self::assertTrue(DatabaseMigration::isCompatibleIntegerDefinition(
            'int NOT NULL AUTO_INCREMENT',
            true,
        ));
        self::assertFalse(DatabaseMigration::isCompatibleIntegerDefinition(
            "smallint NOT NULL DEFAULT '0'",
        ));
    }

    public function testSmartBlocksUpdatePreservesCurrentImageAssets(): void
    {
        $updateHook = file_get_contents(self::$modulePaths['smartblocks'] . '/include/onupdate.php');
        $config     = file_get_contents(self::$modulePaths['smartblocks'] . '/config/config.php');

        self::assertIsString($updateHook);
        self::assertIsString($config);
        self::assertStringNotContainsString('Installer::removeOldAssets', $updateHook);
        self::assertStringNotContainsString("'/images',", $config);
    }

    private static function withoutComments(string $source): string
    {
        $code = '';
        foreach (token_get_all($source) as $token) {
            if (is_string($token)) {
                $code .= $token;
                continue;
            }
            if (T_COMMENT !== $token[0] && T_DOC_COMMENT !== $token[0]) {
                $code .= $token[1];
            }
        }

        return $code;
    }
}
