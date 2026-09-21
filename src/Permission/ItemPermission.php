<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Permission;

final readonly class ItemPermission
{
    public function __construct(
        private int $moduleId,
        private PermissionGatewayInterface $gateway,
    ) {
        if ($moduleId < 1) {
            throw new \InvalidArgumentException('A positive XOOPS module id is required.');
        }
    }

    public static function forModule(object $module): self
    {
        if (!method_exists($module, 'getVar')) {
            throw new \InvalidArgumentException('A XOOPS module object is required.');
        }

        return new self((int) $module->getVar('mid', 'n'), XoopsPermissionGateway::fromCore());
    }

    /** @return list<int> */
    public function grantedGroups(string $name, int $itemId): array
    {
        $this->assertArguments($name, $itemId);

        return $this->gateway->groupIds($name, $itemId, $this->moduleId);
    }

    /** @param list<int> $groupIds @return list<int> */
    public function grantedItems(string $name, array $groupIds): array
    {
        $this->assertName($name);

        return $this->gateway->itemIds($name, $this->normalizeIds($groupIds), $this->moduleId);
    }

    /** @param list<int> $groupIds */
    public function isGranted(string $name, int $itemId, array $groupIds): bool
    {
        $this->assertArguments($name, $itemId);

        return $this->gateway->checkRight($name, $itemId, $this->normalizeIds($groupIds), $this->moduleId);
    }

    /** @param list<int> $groupIds */
    public function replace(string $name, int $itemId, array $groupIds): bool
    {
        $this->assertArguments($name, $itemId);
        if (!$this->gateway->delete($name, $itemId, $this->moduleId)) {
            return false;
        }
        return array_all($this->normalizeIds($groupIds), fn($groupId) => $this->gateway->add($name, $itemId, $groupId, $this->moduleId));
    }

    public function delete(string $name, int $itemId): bool
    {
        $this->assertArguments($name, $itemId);

        return $this->gateway->delete($name, $itemId, $this->moduleId);
    }

    private function assertArguments(string $name, int $itemId): void
    {
        $this->assertName($name);
        if ($itemId < 0) {
            throw new \InvalidArgumentException('Permission item ids cannot be negative.');
        }
    }

    private function assertName(string $name): void
    {
        if (1 !== preg_match('/^[A-Za-z][A-Za-z0-9_]{0,63}$/', $name)) {
            throw new \InvalidArgumentException('Invalid permission name.');
        }
    }

    /** @param array<int, mixed> $ids @return list<int> */
    private function normalizeIds(array $ids): array
    {
        $normalized = [];
        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $normalized[$id] = $id;
            }
        }

        return array_values($normalized);
    }
}
