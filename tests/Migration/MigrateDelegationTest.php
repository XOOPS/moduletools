<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Tests\Migration;

use PHPUnit\Framework\TestCase;
use Xmf\Database\Migrate as XmfMigrate;
use Xoops\ModuleTools\Common\Migrate;

final class MigrateDelegationTest extends TestCase
{
    public function testCompatibilityClassDelegatesThroughInheritance(): void
    {
        self::assertTrue(is_subclass_of(Migrate::class, XmfMigrate::class));
        self::assertSame(XmfMigrate::class, get_parent_class(Migrate::class));
    }
}
