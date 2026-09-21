<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Tests\Internal;

use PHPUnit\Framework\TestCase;
use Xoops\ModuleTools\Internal\PackageLanguage;

final class PackageLanguageTest extends TestCase
{
    public function testEnglishFallbackLoadsPackageCatalog(): void
    {
        self::assertTrue(PackageLanguage::load('common', 'missing-locale'));
        self::assertTrue(defined('_CO_MTOOLS_ALL'));
        self::assertSame('All', constant('_CO_MTOOLS_ALL'));
    }

    public function testUnsafeCatalogNameIsRejected(): void
    {
        self::assertFalse(PackageLanguage::load('../common'));
    }
}
