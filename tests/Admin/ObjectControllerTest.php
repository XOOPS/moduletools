<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Tests\Admin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Xoops\ModuleTools\Admin\ObjectController;
use Xoops\ModuleTools\Tests\Support\FakeObject;

defined('XOBJ_DTYPE_TXTBOX') || define('XOBJ_DTYPE_TXTBOX', 1);
defined('XOBJ_DTYPE_INT') || define('XOBJ_DTYPE_INT', 3);
defined('XOBJ_DTYPE_ARRAY') || define('XOBJ_DTYPE_ARRAY', 6);
defined('XOBJ_DTYPE_STIME') || define('XOBJ_DTYPE_STIME', 9);
defined('XOBJ_DTYPE_MTIME') || define('XOBJ_DTYPE_MTIME', 10);
defined('XOBJ_DTYPE_LTIME') || define('XOBJ_DTYPE_LTIME', 11);

#[CoversClass(ObjectController::class)]
final class ObjectControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        $_POST = [];
    }

    #[Test]
    public function postDataSkipsHiddenReadOnlyAndNonPersistentFieldsAndArraysForScalars(): void
    {
        $object = new FakeObject()
            ->addVar('title', XOBJ_DTYPE_TXTBOX, '')
            ->addVar('uid', XOBJ_DTYPE_INT, 1, ['displayOnForm' => false])
            ->addVar('counter', XOBJ_DTYPE_INT, 0, ['readonly' => true])
            ->addVar('cache', XOBJ_DTYPE_TXTBOX, '', ['persistent' => false])
            ->addVar('tags', XOBJ_DTYPE_ARRAY, [])
            ->addVar('created', XOBJ_DTYPE_LTIME, 0);
        $_POST = [
            'title'   => ['injected'],   // array for a scalar field: strlen(array) in core
            'uid'     => '999',
            'counter' => '999',
            'cache'   => 'x',
            'tags'    => ['a', 'b'],
            'created' => ['date' => '2026-01-02', 'time' => '60'],
        ];

        new ObjectController(new \XoopsPersistableObjectHandler())->postDataToObject($object);

        self::assertSame(
            [['tags', ['a', 'b']], ['created', strtotime('2026-01-02') + 60]],
            $object->setCalls,
        );
    }

    #[Test]
    public function postDataWritesPlainScalars(): void
    {
        $object = new FakeObject()->addVar('title', XOBJ_DTYPE_TXTBOX, '');
        $_POST = ['title' => 'Hello'];

        new ObjectController(new \XoopsPersistableObjectHandler())->postDataToObject($object);

        self::assertSame([['title', 'Hello']], $object->setCalls);
    }
}
