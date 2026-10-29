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
defined('XOBJ_DTYPE_FILE') || define('XOBJ_DTYPE_FILE', 204);
defined('XOBJ_DTYPE_IMAGE') || define('XOBJ_DTYPE_IMAGE', 205);

#[CoversClass(ObjectController::class)]
final class ObjectControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        $_POST = [];
        $_FILES = [];
        unset($_SERVER['REQUEST_METHOD'], $GLOBALS['xoopsSecurity']);
    }

    #[Test]
    public function userSideRefusesWithoutAuthorizationAndNeverReadsItemPermissions(): void
    {
        $handler = $this->storingHandler();

        self::assertFalse(new ObjectController($handler)->storeSmartObject(userSide: true), 'no callback, not a DynamicObject');
        self::assertFalse(new ObjectController($handler)->storeSmartObject(authorize: static fn (): bool => false, userSide: true));
        self::assertSame(['create', 'create'], $handler->calls, 'nothing stored');

        $handler->calls = [];
        self::assertInstanceOf(\XoopsObject::class, new ObjectController($handler)->storeSmartObject(authorize: static fn (): bool => true, userSide: true));
        self::assertSame(['create', 'insert'], $handler->calls, 'stored; the item permissions were not touched');
    }

    #[Test]
    public function adminSideStoresWithoutACallbackAndHandlesItemPermissions(): void
    {
        $handler = $this->storingHandler();

        self::assertInstanceOf(\XoopsObject::class, new ObjectController($handler)->storeSmartObject());
        self::assertSame(['create', 'insert', 'getModuleInfo'], $handler->calls);
    }

    /** A POST with a valid token to a handler that records what the controller asks of it. */
    private function storingHandler(): \XoopsPersistableObjectHandler
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $GLOBALS['xoopsSecurity'] = new class {
            public function check(): bool
            {
                return true;
            }
        };

        return new class extends \XoopsPersistableObjectHandler {
            public string $keyName = 'id';

            /** @var list<string> */
            public array $calls = [];

            public function create($isNew = \true): \XoopsObject
            {
                $this->calls[] = 'create';

                return new class extends \XoopsObject {
                    /** @var array<string, array<string, mixed>> */
                    public array $vars = [];

                    public function isNew(): bool
                    {
                        return true;
                    }

                    /** @return list<string> */
                    public function getErrors(): array
                    {
                        return [];
                    }
                };
            }

            public function insert(\XoopsObject $object, $force = \true): bool
            {
                $this->calls[] = 'insert';

                return true;
            }

            /** @return list<array<string, string>> */
            public function getPermissions(): array
            {
                $this->calls[] = 'getPermissions';

                return [];
            }

            public function getModuleInfo(): ?object
            {
                $this->calls[] = 'getModuleInfo';

                return null;
            }
        };
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

    #[Test]
    public function uploadsReachOnlyWritableDeclaredUploadFields(): void
    {
        $object = new FakeObject()
            ->addVar('image', XOBJ_DTYPE_TXTBOX, '', ['custom_type' => XOBJ_DTYPE_IMAGE])
            ->addVar('logo', XOBJ_DTYPE_TXTBOX, 'old.png', ['custom_type' => XOBJ_DTYPE_IMAGE, 'readonly' => true])
            ->addVar('banner', XOBJ_DTYPE_TXTBOX, 'old.png', ['custom_type' => XOBJ_DTYPE_FILE, 'displayOnForm' => false])
            ->addVar('uid', XOBJ_DTYPE_INT, 1);
        $_FILES = [
            'upload_image' => ['name' => 'a.png'],
            'logo'         => ['name' => 'b.png'],
            'banner'       => ['name' => 'c.png'],
            'uid'          => ['name' => 'd.png'], // not an upload field: no file may be stored for it
        ];
        \XoopsMediaUploader::$fetched = [];
        $handler = new class extends \XoopsPersistableObjectHandler {
            public function getImagePath(bool $path = false): string
            {
                return sys_get_temp_dir();
            }
        };

        new \ReflectionMethod(ObjectController::class, 'receiveUploads')->invoke(new ObjectController($handler), $object);

        self::assertSame(['upload_image'], \XoopsMediaUploader::$fetched, 'refused fields never reach the uploader');
        self::assertSame([['image', 'saved-upload_image']], $object->setCalls);
        // A refused upload is reported, so storeFromDefaultForm() does not save past the lost file.
        self::assertSame([
            'logo: this field does not accept uploads.',
            'banner: this field does not accept uploads.',
            'uid: this field does not accept uploads.',
        ], $object->errors);
    }
}
