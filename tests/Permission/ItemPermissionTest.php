<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Tests\Permission;

use PHPUnit\Framework\TestCase;
use Xoops\ModuleTools\Permission\ItemPermission;
use Xoops\ModuleTools\Permission\PermissionGatewayInterface;

final class ItemPermissionTest extends TestCase
{
    public function testReadsAndWritesPermissionsWithinOneModuleBoundary(): void
    {
        $gateway = new RecordingPermissionGateway();
        $service = new ItemPermission(42, $gateway);

        $gateway->groups = [2, 4];
        $gateway->items  = [7, 9];
        $gateway->right  = true;

        self::assertSame([2, 4], $service->grantedGroups('item_view', 7));
        self::assertSame([7, 9], $service->grantedItems('item_view', [1, 2]));
        self::assertTrue($service->isGranted('item_view', 7, [2]));
        self::assertTrue($service->replace('item_view', 7, [2, 4]));
        self::assertSame(
            [
                ['delete', 42, 'item_view', 7],
                ['add', 42, 'item_view', 7, 2],
                ['add', 42, 'item_view', 7, 4],
            ],
            $gateway->writes,
        );
    }

    public function testRejectsInvalidPermissionNamesAndIdentifiers(): void
    {
        $service = new ItemPermission(42, new RecordingPermissionGateway());

        $this->expectException(\InvalidArgumentException::class);
        $service->replace('../bad', 1, [2]);
    }
}

final class RecordingPermissionGateway implements PermissionGatewayInterface
{
    /** @var list<int> */
    public array $groups = [];
    /** @var list<int> */
    public array $items = [];
    public bool $right = false;
    /** @var list<array<int, int|string>> */
    public array $writes = [];

    public function groupIds(string $name, int $itemId, int $moduleId): array
    {
        return $this->groups;
    }

    public function itemIds(string $name, array $groupIds, int $moduleId): array
    {
        return $this->items;
    }

    public function checkRight(string $name, int $itemId, array $groupIds, int $moduleId): bool
    {
        return $this->right;
    }

    public function delete(string $name, int $itemId, int $moduleId): bool
    {
        $this->writes[] = ['delete', $moduleId, $name, $itemId];

        return true;
    }

    public function add(string $name, int $itemId, int $groupId, int $moduleId): bool
    {
        $this->writes[] = ['add', $moduleId, $name, $itemId, $groupId];

        return true;
    }
}
