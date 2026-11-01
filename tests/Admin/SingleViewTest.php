<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Tests\Admin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Xoops\ModuleTools\Admin\ObjectRow;
use Xoops\ModuleTools\Admin\SingleView;
use Xoops\ModuleTools\Tests\Support\FakeObject;

defined('XOBJ_DTYPE_TXTBOX') || define('XOBJ_DTYPE_TXTBOX', 1);

#[CoversClass(SingleView::class)]
final class SingleViewTest extends TestCase
{
    public function testAFieldHiddenFromSingleViewIsNotRenderedEvenWhenRegistered(): void
    {
        $object = new FakeObject()
            ->addVar('title', XOBJ_DTYPE_TXTBOX, 'Visible title')
            ->addVar('secret', XOBJ_DTYPE_TXTBOX, 'hidden-value', ['displayOnSingleView' => false]);
        $view = new SingleView($object);
        $view->addRow(new ObjectRow('title'));
        $view->addRow(new ObjectRow('secret'));

        $html = (string) $view->fetch();

        self::assertStringContainsString('Visible title', $html);
        self::assertStringNotContainsString('hidden-value', $html);
        self::assertStringNotContainsString('secret', $html);
    }
}
