<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Tests\Admin;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Xoops\ModuleTools\Admin\Export;

final class ExportTest extends TestCase
{
    /** @return iterable<string, array{string, string}> */
    public static function cells(): iterable
    {
        yield 'formula' => ['=HYPERLINK("http://evil")', "'=HYPERLINK(\"http://evil\")"];
        yield 'plus' => ['+cmd|calc', "'+cmd|calc"];
        yield 'at' => ['@SUM(1)', "'@SUM(1)"];
        yield 'tab' => ["\t=1", "'\t=1"];
        yield 'negative number stays numeric' => ['-12.5', '-12.5'];
        yield 'positive sign is still a formula' => ['+1', "'+1"];
        yield 'positive exponent is still a formula' => ['+1e3', "'+1e3"];
        yield 'unsigned number' => ['1e3', '1e3'];
        yield 'plain text' => ['hello', 'hello'];
        yield 'empty' => ['', ''];
    }

    #[DataProvider('cells')]
    public function testNeutralisesSpreadsheetFormulaPrefixes(string $input, string $expected): void
    {
        $method = new \ReflectionMethod(Export::class, 'csvCell');

        self::assertSame($expected, $method->invoke(null, $input));
    }
}
