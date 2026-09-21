<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Persistence;

use Xoops\ModuleTools\Common\SysUtility;

class PersistableHandler extends \XoopsPersistableObjectHandler
{
    public string $_itemname;
    public string $_moduleName;
    public string $summaryName;
    public string $_page;
    public string $_modulePath;
    public string $_moduleUrl;
    public string $_uploadPath;
    public string $_uploadUrl;
    public array $permissionsArray = [];
    public array $_allowedMimeTypes = [];
    public int $_maxFileSize = 1000000;
    public int $_maxWidth = 500;
    public int $_maxHeight = 500;

    public function __construct(
        ?\XoopsDatabase $db,
        string $itemName,
        string $keyName,
        string|false $identifierName,
        string|false $summaryName,
        string $moduleName,
        ?string $tableName = null,
        ?string $className = null,
    ) {
        $className ??= 'XoopsModules\\' . ucfirst($moduleName) . '\\' . str_replace(' ', '', ucwords(str_replace('_', ' ', $itemName)));
        $tableName ??= $moduleName . '_' . $itemName;
        parent::__construct($db, $tableName, $className, $keyName, $identifierName ?: '');
        $this->initialize($itemName, $moduleName, (string) $summaryName);
    }

    protected function initialize(string $itemName, string $moduleName, string $summaryName): void
    {
        $this->_itemname = $itemName;
        $this->_moduleName = $moduleName;
        $this->summaryName = $summaryName;
        $this->_page = $itemName . '.php';
        $this->_modulePath = XOOPS_ROOT_PATH . '/modules/' . $moduleName . '/';
        $this->_moduleUrl = XOOPS_URL . '/modules/' . $moduleName . '/';
        $this->_uploadPath = XOOPS_UPLOAD_PATH . '/' . $moduleName . '/';
        $this->_uploadUrl = XOOPS_UPLOAD_URL . '/' . $moduleName . '/';
    }

    public function create($isNew = true)
    {
        $class = $this->className;
        $object = new $class();
        if (property_exists($object, 'handler')) {
            $object->handler = $this;
        }
        if (method_exists($object, 'setImageDir')) {
            $object->setImageDir($this->getImageUrl(), $this->getImagePath());
        }
        if ($isNew) {
            $object->setNew();
        }

        return $object;
    }

    public function setUploaderConfig(string|false $path = false, array|false $mimeTypes = false, int|false $maxFileSize = false, int|false $maxWidth = false, int|false $maxHeight = false): void
    {
        if (is_string($path)) {
            $this->_uploadPath = $path;
        }
        if (is_array($mimeTypes)) {
            $this->_allowedMimeTypes = $mimeTypes;
        }
        if (false !== $maxFileSize) {
            $this->_maxFileSize = $maxFileSize;
        }
        if (false !== $maxWidth) {
            $this->_maxWidth = $maxWidth;
        }
        if (false !== $maxHeight) {
            $this->_maxHeight = $maxHeight;
        }
    }

    public function getImageUrl(bool $trailingSlash = false): string
    {
        $url = $this->_uploadUrl . $this->_itemname;
        return $trailingSlash ? $url . '/' : $url;
    }

    public function getImagePath(bool $trailingSlash = false): string
    {
        $path = $this->_uploadPath . $this->_itemname;
        SysUtility::createFolder($path);
        return $trailingSlash ? $path . DIRECTORY_SEPARATOR : $path;
    }

    public function addPermission(string $name, string $caption, string|false $description = false): void
    {
        $this->permissionsArray[] = ['perm_name' => $name, 'caption' => $caption, 'description' => $description];
    }

    public function getPermissions(): array
    {
        return $this->permissionsArray;
    }

    public function getModuleInfo(): object|false
    {
        return \XoopsModule::getByDirname($this->_moduleName);
    }

    public function getModuleConfig(): array
    {
        $handler = xoops_getHandler('config');
        $module = $this->getModuleInfo();
        return is_object($module) ? $handler->getConfigsByCat(0, (int) $module->getVar('mid', 'n')) : [];
    }

    /**
     * Compatibility-shaped read used by converted modules. Debug and raw-SQL
     * arguments are intentionally ignored; callers must express filtering with Criteria.
     */
    public function getObjects2(?\CriteriaElement $criteria = null, bool $idAsKey = false, bool $asObject = true, mixed $sql = null, mixed $debug = null): array
    {
        return parent::getObjects($criteria, $idAsKey, $asObject);
    }

    public function setGrantedObjectsCriteria(\CriteriaCompo $criteria, string $permissionName): bool
    {
        $module = $this->getModuleInfo();
        if (!is_object($module)) {
            return false;
        }
        $user = self::runtimeUser();
        $groups = is_object($user) ? $user->getGroups() : [XOOPS_GROUP_ANONYMOUS];
        $ids = \Xoops\ModuleTools\Permission\ItemPermission::forModule($module)->grantedItems($permissionName, array_map(intval(...), (array) $groups));
        if ([] === $ids) {
            return false;
        }
        $criteria->add(new \Criteria($this->keyName, array_map(intval(...), $ids), 'IN'));
        return true;
    }

    /** @legacy-global-accessor */
    private static function runtimeUser(): mixed
    {
        return $GLOBALS['xoopsUser'] ?? null;
    }
}
