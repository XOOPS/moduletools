<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Tests\Common;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Xoops\ModuleTools\Common\Blocksadmin;
use Xoops\ModuleTools\Tests\Support\FakeBlock;
use Xoops\ModuleTools\Tests\Support\FakeResolvingBlock;
use Xoops\ModuleTools\Tests\Support\FakeSettableBlock;

/**
 * Block titles can be stored as a language key (`_MI_*`) that core translates for display.
 * Saving a block admin form must not turn that key into the text it displayed.
 * The three fake blocks are the three cores the library runs on.
 */
#[CoversClass(Blocksadmin::class)]
final class BlocksadminLabelTest extends TestCase
{
    private const KEY = '_MI_DEMO_BLOCK_POSTS';
    private const SHOWN = 'Recent Posts';

    #[Test]
    public function labelsAreReadThroughTheCoreResolverWhenItExists(): void
    {
        $block = self::resolvingBlock(self::KEY);

        self::assertSame(self::SHOWN, self::call('blockLabel', $block, 'title'));
        self::assertSame(self::SHOWN, self::call('blockLabel', $block, 'name'));
    }

    #[Test]
    public function labelsAreReadFromTheRawColumnOnACoreWithoutAResolver(): void
    {
        $block = new FakeBlock('Plain title');

        self::assertSame('Plain title', self::call('blockLabel', $block, 'title'));
        self::assertSame('Plain title', self::call('blockLabel', $block, 'name'));
    }

    #[Test]
    public function anUnchangedLabelKeepsTheStoredKeyWithoutACoreSetter(): void
    {
        $block = self::resolvingBlock(self::KEY);

        self::call('storeBlockLabel', $block, 'title', self::SHOWN);

        self::assertSame(self::KEY, $block->stored['title']);
    }

    #[Test]
    public function anEditedLabelIsStoredWithoutACoreSetter(): void
    {
        $block = self::resolvingBlock(self::KEY);

        self::call('storeBlockLabel', $block, 'title', 'My posts');

        self::assertSame('My posts', $block->stored['title']);
    }

    #[Test]
    public function anUnchangedLiteralTitleStaysAsItIs(): void
    {
        $block = self::resolvingBlock('Hand-written title');

        self::call('storeBlockLabel', $block, 'title', 'Hand-written title');

        self::assertSame('Hand-written title', $block->stored['title']);
    }

    #[Test]
    public function theCoreSetterDecidesWhenItExists(): void
    {
        $block = new FakeSettableBlock(self::KEY, self::KEY, self::SHOWN);

        self::call('storeBlockLabel', $block, 'title', self::SHOWN);
        self::assertSame(self::KEY, $block->stored['title']);

        self::call('storeBlockLabel', $block, 'title', 'My posts');

        self::assertSame([self::SHOWN, 'My posts'], $block->setTitleCalls);
        self::assertSame('My posts', $block->stored['title']);
    }

    #[Test]
    public function aCoreWithoutResolverOrSetterStoresTheSubmittedText(): void
    {
        $block = new FakeBlock('Old title');

        self::call('storeBlockLabel', $block, 'title', 'New title');

        self::assertSame('New title', $block->stored['title']);
    }

    private static function call(string $method, mixed ...$arguments): mixed
    {
        return (new \ReflectionMethod(Blocksadmin::class, $method))->invoke(null, ...$arguments);
    }

    private static function resolvingBlock(string $stored): FakeResolvingBlock
    {
        return new FakeResolvingBlock($stored, self::KEY, self::SHOWN);
    }
}
