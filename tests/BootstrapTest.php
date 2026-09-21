<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Xoops\ModuleTools\Bootstrap;
use Xoops\ModuleTools\Common\PaginationState;
use Xoops\ModuleTools\Module\ConsumerRuntime;
use Xoops\ModuleTools\Module\Dependency;

final class BootstrapTest extends TestCase
{
    public function testExpandedApiVersionSupportsOldAndNewConsumers(): void
    {
        self::assertSame('1.1.0', Bootstrap::apiVersion());
        self::assertTrue(ConsumerRuntime::isReady('1.0.0'));
        self::assertTrue(ConsumerRuntime::isReady('1.1.0'));
        self::assertFalse(ConsumerRuntime::isReady('1.2.0'));
        self::assertSame('1.1.0', Bootstrap::checkRuntime()['api_version']);
    }

    /** @return iterable<string, array{string}> */
    public static function consumerApiClasses(): iterable
    {
        foreach ([
            'Common\\Blocksadmin',
            'Common\\Configurator',
            'Common\\Db',
            'Common\\DirectoryChecker',
            'Common\\FilesManagement',
            'Common\\TestdataButtons',
            'Common\\TestdataSample',
            'Common\\UpdateChecker',
            'Module\\ConsumerRuntime',
            'Module\\Dependency',
            'Module\\Installer',
            'Module\\ModuleContext',
        ] as $relativeClass) {
            yield $relativeClass => [$relativeClass];
        }
    }

    public function testCoreProviderNeedsNoInstalledModule(): void
    {
        $status = Bootstrap::checkRuntime('1.0.0', '1.2.0');

        self::assertTrue($status['ok']);
        self::assertSame('1.5.0', $status['module_version']);
    }

    public function testLegacyBootstrapConstantsRemainAvailable(): void
    {
        self::assertSame('mtools', Bootstrap::MODULE_DIRNAME);
        self::assertSame('1.1.0', Bootstrap::MIN_MODULE_VERSION);
        self::assertSame(Bootstrap::MODULE_DIRNAME, \XoopsModules\Mtools\Bootstrap::MODULE_DIRNAME);
    }

    public function testLegacyModuleDependencyIsSatisfiedByCoreCapability(): void
    {
        $status = Dependency::checkModule('mtools', '1.2.0', true);

        self::assertTrue($status['ok']);
        self::assertSame(Bootstrap::VERSION, $status['module_version']);
        self::assertSame('', ConsumerRuntime::dependencyError('1.0.0', '1.2.0'));
    }

    public function testLegacyNamespaceResolvesToCoreLibrary(): void
    {
        self::assertTrue(class_exists(\XoopsModules\Mtools\Common\PaginationState::class));
        self::assertTrue(is_a(
            \XoopsModules\Mtools\Common\PaginationState::class,
            PaginationState::class,
            true,
        ));
    }

    #[DataProvider('consumerApiClasses')]
    public function testBundledConsumerApisResolveThroughBothNamespaces(string $relativeClass): void
    {
        $modernClass = 'Xoops\\ModuleTools\\' . $relativeClass;
        $legacyClass = 'XoopsModules\\Mtools\\' . $relativeClass;

        $modernExists = class_exists($modernClass) || interface_exists($modernClass) || trait_exists($modernClass);
        $legacyExists = class_exists($legacyClass) || interface_exists($legacyClass) || trait_exists($legacyClass);

        self::assertTrue($modernExists, $modernClass);
        self::assertTrue($legacyExists, $legacyClass);
        if (!trait_exists($modernClass)) {
            self::assertTrue(is_a($legacyClass, $modernClass, true), $legacyClass);
        }
    }

    public function testPaginationContractRunsThroughModernNamespace(): void
    {
        $state = new PaginationState(total: 101, limit: 20, currentPage: 6);

        self::assertSame(6, $state->currentPage());
        self::assertSame(100, $state->offset());
        self::assertSame(101, $state->rangeEnd());
    }

}
