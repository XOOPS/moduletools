<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Tests\Form;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Xoops\ModuleTools\Form\ObjectFormBuilder;

#[CoversClass(ObjectFormBuilder::class)]
final class ObjectFormBuilderTest extends TestCase
{
    protected function setUp(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/modules/quotes/admin/quote.php';
    }

    protected function tearDown(): void
    {
        unset($_SERVER['SCRIPT_NAME']);
    }

    #[Test]
    public function cancelNavigatesToALocalUrlWithoutExecutingIt(): void
    {
        self::assertSame("onclick='location.href=\"index.php?op=list\"'", ObjectFormBuilder::cancelHandler('index.php?op=list'));
        self::assertSame("onclick='history.back()'", ObjectFormBuilder::cancelHandler('history.back()'));

        // A quote, tag or ampersand never reaches the attribute or the script raw.
        $handler = ObjectFormBuilder::cancelHandler("index.php?x='\"<>&");
        self::assertSame("onclick='location.href=\"index.php?x=\\u0027\\u0022\\u003C\\u003E\\u0026\"'", $handler);
    }

    #[Test]
    #[DataProvider('unsafeTargets')]
    public function cancelFallsBackToTheCurrentScriptForUnsafeTargets(string $target): void
    {
        self::assertSame("onclick='location.href=\"/modules/quotes/admin/quote.php\"'", ObjectFormBuilder::cancelHandler($target), $target);
    }

    /** @return iterable<string, array{string}> */
    public static function unsafeTargets(): iterable
    {
        yield 'javascript' => ['javascript:alert(1)'];
        yield 'data' => ['data:text/html,x'];
        yield 'absolute' => ['https://evil.test/'];
        yield 'protocol relative' => ['//evil.test/x'];
        yield 'control char' => ["index\x00.php"];
        yield 'empty' => [''];
    }
}
