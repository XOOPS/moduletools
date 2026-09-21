<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Tests\Internal\Presentation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Xoops\ModuleTools\Internal\Presentation\ObjectValuePresenter;
use Xoops\ModuleTools\Tests\Support\FakeObject;

defined('XOBJ_DTYPE_TXTBOX') || define('XOBJ_DTYPE_TXTBOX', 1);
defined('XOBJ_DTYPE_TXTAREA') || define('XOBJ_DTYPE_TXTAREA', 2);
defined('XOBJ_DTYPE_URL') || define('XOBJ_DTYPE_URL', 4);
defined('XOBJ_DTYPE_OTHER') || define('XOBJ_DTYPE_OTHER', 7);

#[CoversClass(ObjectValuePresenter::class)]
final class ObjectValuePresenterTest extends TestCase
{
    private function object(): FakeObject
    {
        return new FakeObject()
            ->addVar('title', XOBJ_DTYPE_TXTBOX, "O'Brien & <Sons>")
            ->addVar('body', XOBJ_DTYPE_TXTAREA, "line one\nline two")
            ->addVar('url', XOBJ_DTYPE_URL, "http://x' onfocus='alert(1)")
            ->addVar('tags', XOBJ_DTYPE_OTHER, ['a&b', 'c']);
    }

    #[Test]
    public function textFieldsUseCoreOutputExactlyOnce(): void
    {
        $object = $this->object();

        self::assertSame('O&apos;Brien &amp; &lt;Sons&gt;', ObjectValuePresenter::html($object, 'title'), 'no double encoding');
        self::assertSame("line one<br />\nline two", ObjectValuePresenter::html($object, 'body'), 'rendered, not literal');
    }

    #[Test]
    public function rawTypesAreEscapedBecauseCoreDoesNot(): void
    {
        $object = $this->object();

        self::assertSame('http://x&apos; onfocus=&apos;alert(1)', ObjectValuePresenter::html($object, 'url'));
        self::assertSame('a&amp;b, c', ObjectValuePresenter::html($object, 'tags'));
        self::assertSame('', ObjectValuePresenter::html($object, 'missing'));
    }

    #[Test]
    public function plainReturnsStoredValuesForCsv(): void
    {
        $object = $this->object();

        self::assertSame("O'Brien & <Sons>", ObjectValuePresenter::plain($object, 'title'));
        self::assertSame("line one\nline two", ObjectValuePresenter::plain($object, 'body'));
        self::assertSame('a&b, c', ObjectValuePresenter::plain($object, 'tags'));
    }
}
