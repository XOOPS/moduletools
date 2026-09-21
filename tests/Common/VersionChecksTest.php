<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Tests\Common;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Xoops\ModuleTools\Common\SysUtility;

final class VersionChecksTest extends TestCase
{
    private function message(string $dirname, string $suffix): string
    {
        $method = new \ReflectionMethod(SysUtility::class, 'versionCheckMessage');

        return (string) $method->invoke(null, $dirname, $suffix, 'fallback %s %s');
    }

    #[Test]
    public function aFailedCheckAlwaysHasAMessage(): void
    {
        // Nothing defined for this module: the package catalog answers, never "Undefined constant".
        self::assertStringContainsString('%s', $this->message('vcnone', 'ERROR_BAD_XOOPS'));
        self::assertSame(constant('_CO_MTOOLS_ERROR_BAD_PHP'), $this->message('vcnone', 'ERROR_BAD_PHP'));
    }

    #[Test]
    public function theConsumersLegacyConstantWins(): void
    {
        define('_AM_VCLEGACY_ERROR_BAD_XOOPS', 'legacy %s %s');
        define('_CO_VCLEGACY_ERROR_BAD_PHP', 'consumer %s %s');

        self::assertSame('legacy %s %s', $this->message('vclegacy', 'ERROR_BAD_XOOPS'));
        self::assertSame('consumer %s %s', $this->message('vclegacy', 'ERROR_BAD_PHP'));
    }
}
