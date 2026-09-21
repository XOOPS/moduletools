<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Tests\Common;

use PHPUnit\Framework\TestCase;
use Xoops\ModuleTools\Common\Text;

final class TextTest extends TestCase
{
    public function testExactTruncationCountsEntitiesAndMultibyteCharacters(): void
    {
        foreach (['face; more' => 'face', '&amp;abcdef' => '&amp;abc', '&#38;abcdef' => '&#38;abc', '&#x26;abcdef' => '&#x26;abc', 'éé&amp;abcdef' => 'éé&amp;a'] as $input => $expected) {
            self::assertSame($expected, Text::truncateHtml($input, 4, '', true));
        }
    }
}
