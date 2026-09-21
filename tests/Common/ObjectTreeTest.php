<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Tests\Common;

use PHPUnit\Framework\TestCase;
use Xoops\ModuleTools\Common\ObjectTree;

final class ObjectTreeTest extends TestCase
{
    public function testLegacySelectMethodReturnsOptionsArray(): void
    {
        $tree = new class extends ObjectTree {
            public function __construct()
            {
            }

            public function makeSelBoxOptionsArray($fieldName, $key, &$optionsArray, $prefix_orig, $prefix_curr = '')
            {
                $optionsArray[7] = $prefix_orig . $fieldName . $key;
                return $optionsArray;
            }
        };
        self::assertSame([0 => '', 7 => '--title3'], $tree->makeSelBox('category', 'title', '--', '', true, 3));
    }
}
