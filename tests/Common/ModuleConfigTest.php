<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Tests\Common;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Xoops\ModuleTools\Common\ModuleConfig;

final class ModuleConfigTest extends TestCase
{
    #[Test]
    public function preserves_the_original_positional_constructor_contract(): void
    {
        $config = new ModuleConfig('', [], [], [], [], [], [], [], [], [], 'copyright');

        self::assertSame('copyright', $config->modCopyright);
        self::assertSame([], $config->paths);
    }
}
