<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Tests\Migration;

use PHPUnit\Framework\TestCase;
use Xoops\Helpers\Service\Path;
use Xoops\Helpers\Service\Url;
use Xoops\ModuleTools\Module\ModuleContext;

final class ModuleContextDelegationTest extends TestCase
{
    public function testPathsAndUrlsMatchHelpersServices(): void
    {
        $context = ModuleContext::for('quotes');

        self::assertSame(Path::module('quotes', 'admin/index.php'), $context->path('admin/index.php'));
        self::assertSame(Path::module('quotes', 'admin/index.php'), $context->adminPath('index.php'));
        self::assertSame(Url::module('quotes', 'index.php'), $context->url('index.php'));
        self::assertSame(Url::module('quotes', 'admin/index.php'), $context->adminUrl('index.php'));
    }
}
