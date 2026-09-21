<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Admin;

use Xmf\Request;
use Xoops\ModuleTools\Permission\ItemPermission;

/** Standard POST-to-XoopsObject CRUD coordinator for module admin pages. */
final readonly class ObjectController
{
    public function __construct(public \XoopsPersistableObjectHandler $handler)
    {
    }

    public function postDataToObject(\XoopsObject $object): void
    {
        foreach ($object->vars as $name => $metadata) {
            if (false === ($metadata['persistent'] ?? true) || !array_key_exists($name, $_POST)) {
                continue;
            }
            $value = $_POST[$name];
            if (in_array((int) $metadata['data_type'], [XOBJ_DTYPE_STIME, XOBJ_DTYPE_MTIME, XOBJ_DTYPE_LTIME], true)) {
                if (is_array($value) && isset($value['date'])) {
                    $value = strtotime((string) $value['date']) + (int) ($value['time'] ?? 0);
                } elseif (!is_numeric($value)) {
                    $value = strtotime((string) $value) ?: 0;
                }
            }
            $object->setVar((string) $name, $value);
        }
    }

    public function storeFromDefaultForm(string $createdMessage, string $modifiedMessage, string|false|null $redirectPage = false, bool $debug = false, bool $extended = false): \XoopsObject|false
    {
        if ('POST' !== ($_SERVER['REQUEST_METHOD'] ?? 'GET')) {
            return false;
        }
        self::assertValidToken();

        $key = (string) $this->handler->keyName;
        $id = Request::getInt($key, 0, 'POST');
        $object = $id > 0 ? $this->handler->get($id) : $this->handler->create();
        if (!$object instanceof \XoopsObject || ($id > 0 && $object->isNew())) {
            return false;
        }
        $isNew = $object->isNew();
        $this->postDataToObject($object);
        $this->receiveUploads($object);
        if (!$this->handler->insert($object)) {
            return false;
        }

        if (method_exists($this->handler, 'getPermissions') && method_exists($this->handler, 'getModuleInfo')) {
            $module = $this->handler->getModuleInfo();
            if (is_object($module)) {
                $permissions = ItemPermission::forModule($module);
                foreach ((array) $this->handler->getPermissions() as $definition) {
                    $name = (string) ($definition['perm_name'] ?? '');
                    if ('' !== $name) {
                        $permissions->replace($name, (int) $object->getVar($key, 'n'), array_map(intval(...), (array) ($_POST[$name] ?? [])));
                    }
                }
            }
        }

        if (null !== $redirectPage) {
            $target = false === $redirectPage ? (string) ($_SERVER['HTTP_REFERER'] ?? xoops_getenv('SCRIPT_NAME')) : $redirectPage;
            redirect_header($target, 2, $isNew ? $createdMessage : $modifiedMessage);
        }

        return $object;
    }

    public function storeSmartObject(bool $debug = false, bool $extended = false): \XoopsObject|false
    {
        return $this->storeFromDefaultForm('', '', null, $debug, $extended);
    }

    public function storeSmartObjectD(): \XoopsObject|false
    {
        return $this->storeSmartObject(true);
    }

    public function handleObjectDeletion(string|false $confirmMessage = false, string $operation = 'del', bool $userSide = false): void
    {
        $key = (string) $this->handler->keyName;
        $id = 'POST' === ($_SERVER['REQUEST_METHOD'] ?? 'GET') ? Request::getInt($key, 0, 'POST') : Request::getInt($key, 0, 'GET');
        $object = $id > 0 ? $this->handler->get($id) : false;
        if (!$object instanceof \XoopsObject || $object->isNew()) {
            redirect_header((string) ($_SERVER['HTTP_REFERER'] ?? xoops_getenv('SCRIPT_NAME')), 3, defined('_NOPERM') ? _NOPERM : 'Record not found.');
            return;
        }
        if ('POST' === ($_SERVER['REQUEST_METHOD'] ?? 'GET') && Request::getInt('confirm', 0, 'POST')) {
            self::assertValidToken();
            $redirect = Request::getString('redirect_page', xoops_getenv('SCRIPT_NAME'), 'POST');
            $ok = $this->handler->delete($object);
            redirect_header($redirect, 2, $ok ? (defined('_DELETEDSUCCESS') ? _DELETEDSUCCESS : 'Deleted.') : (defined('_ERRORS') ? _ERRORS : 'Delete failed.'));
        }

        $message = false === $confirmMessage ? (defined('_DELETE') ? _DELETE : 'Delete this record?') : $confirmMessage;
        xoops_confirm(
            ['op' => $operation, $key => $id, 'confirm' => 1, 'redirect_page' => (string) ($_SERVER['HTTP_REFERER'] ?? xoops_getenv('SCRIPT_NAME'))],
            xoops_getenv('SCRIPT_NAME'),
            sprintf($message, (string) $object->getVar((string) $this->handler->identifierName, 's')),
            defined('_DELETE') ? _DELETE : 'Delete',
        );
    }

    public function handleObjectDeletionFromUserSide(string|false $confirmMessage = false, string $operation = 'del'): void
    {
        $this->handleObjectDeletion($confirmMessage, $operation, true);
    }

    private function receiveUploads(\XoopsObject $object): void
    {
        if (!method_exists($this->handler, 'getImagePath')) {
            return;
        }
        foreach ($_FILES as $field => $file) {
            $name = str_starts_with((string) $field, 'upload_') ? substr((string) $field, 7) : (string) $field;
            if (!isset($object->vars[$name], $file['name']) || '' === (string) $file['name']) {
                continue;
            }
            $uploader = new \XoopsMediaUploader(
                $this->handler->getImagePath(true),
                (array) ($this->handler->_allowedMimeTypes ?? []),
                (int) ($this->handler->_maxFileSize ?? 1_000_000),
                (int) ($this->handler->_maxWidth ?? 0),
                (int) ($this->handler->_maxHeight ?? 0),
            );
            if ($uploader->fetchMedia((string) $field) && $uploader->upload()) {
                $object->setVar($name, $uploader->getSavedFileName());
            } else {
                $object->setErrors($uploader->getErrors(false));
            }
        }
    }

    /** @legacy-global-accessor */
    private static function runtimeSecurity(): mixed
    {
        return $GLOBALS['xoopsSecurity'] ?? null;
    }

    /** Fail closed: without a security service no token can be checked, so no write happens. */
    private static function assertValidToken(): void
    {
        $security = self::runtimeSecurity();
        if (!is_object($security) || !method_exists($security, 'check') || !$security->check()) {
            throw new \RuntimeException(defined('_NOPERM') ? _NOPERM : 'Invalid security token.');
        }
    }
}
