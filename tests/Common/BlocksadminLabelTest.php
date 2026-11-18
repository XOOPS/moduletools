<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Tests\Common;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Xoops\ModuleTools\Common\Blocksadmin;

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
        $block = self::rawBlock('Plain title');

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
        $block = self::blockWithSetter(self::KEY);

        self::call('storeBlockLabel', $block, 'title', self::SHOWN);
        self::call('storeBlockLabel', $block, 'title', 'My posts');

        self::assertSame([self::SHOWN, 'My posts'], $block->setTitleCalls);
        self::assertSame('My posts', $block->stored['title']);
    }

    #[Test]
    public function aCoreWithoutResolverOrSetterStoresTheSubmittedText(): void
    {
        $block = self::rawBlock('Old title');

        self::call('storeBlockLabel', $block, 'title', 'New title');

        self::assertSame('New title', $block->stored['title']);
    }

    private static function call(string $method, mixed ...$arguments): mixed
    {
        return (new \ReflectionMethod(Blocksadmin::class, $method))->invoke(null, ...$arguments);
    }

    /** XOOPS before 2.7.5: the raw columns and nothing else. */
    private static function rawBlock(string $stored): \XoopsObject
    {
        return new class ($stored) extends \XoopsObject {
            /** @var array<string, string> */
            public array $stored;

            public function __construct(string $stored)
            {
                $this->stored = ['title' => $stored, 'name' => $stored];
            }

            public function getVar($key, $format = 's')
            {
                return $this->stored[$key] ?? '';
            }

            public function setVar($key, $value, $not_gpc = false): void
            {
                $this->stored[$key] = (string) $value;
            }
        };
    }

    /** XOOPS 2.7.5: title() and name() translate a stored key; there is no setTitle(). */
    private static function resolvingBlock(string $stored): \XoopsObject
    {
        return new class ($stored, self::KEY, self::SHOWN) extends \XoopsObject {
            /** @var array<string, string> */
            public array $stored;

            public function __construct(string $stored, private readonly string $key, private readonly string $shown)
            {
                $this->stored = ['title' => $stored, 'name' => $stored];
            }

            public function getVar($key, $format = 's')
            {
                return $this->stored[$key] ?? '';
            }

            public function setVar($key, $value, $not_gpc = false): void
            {
                $this->stored[$key] = (string) $value;
            }

            public function title($format = ''): string
            {
                return $this->key === $this->stored['title'] ? $this->shown : $this->stored['title'];
            }

            public function name($format = ''): string
            {
                return $this->key === $this->stored['name'] ? $this->shown : $this->stored['name'];
            }
        };
    }

    /** XOOPS 2.8: setTitle() applies the keep-the-key rule itself. */
    private static function blockWithSetter(string $stored): \XoopsObject
    {
        return new class ($stored, self::KEY, self::SHOWN) extends \XoopsObject {
            /** @var array<string, string> */
            public array $stored;
            /** @var list<string> */
            public array $setTitleCalls = [];

            public function __construct(string $stored, private readonly string $key, private readonly string $shown)
            {
                $this->stored = ['title' => $stored, 'name' => $stored];
            }

            public function getVar($key, $format = 's')
            {
                return $this->stored[$key] ?? '';
            }

            public function setVar($key, $value, $not_gpc = false): void
            {
                $this->stored[$key] = (string) $value;
            }

            public function title($format = ''): string
            {
                return $this->key === $this->stored['title'] ? $this->shown : $this->stored['title'];
            }

            public function setTitle(string $title): void
            {
                $this->setTitleCalls[] = $title;
                if ($this->key === $this->stored['title'] && $title === $this->shown) {
                    return;
                }
                $this->stored['title'] = $title;
            }
        };
    }
}
