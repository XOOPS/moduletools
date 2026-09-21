<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Tests\Common;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Xoops\ModuleTools\Common\Db;

#[CoversClass(Db::class)]
final class DbTest extends TestCase
{
    #[Test]
    public function enumValuesParsesEnumAndSetColumnTypes(): void
    {
        self::assertSame(['a', 'b'], Db::enumValues("enum('a','b')"));
        self::assertSame(['draft', 'published', 'archived'], Db::enumValues("enum('draft','published','archived')"));
        self::assertSame(['x', 'y'], Db::enumValues("set('x','y')"));
        self::assertSame(["it's", 'a,b'], Db::enumValues("enum('it''s','a,b')"));
        self::assertSame(['only'], Db::enumValues("ENUM('only')"));
    }

    #[Test]
    public function enumValuesIsEmptyForOtherColumnTypes(): void
    {
        self::assertSame([], Db::enumValues('varchar(255)'));
        self::assertSame([], Db::enumValues('int unsigned'));
        self::assertSame([], Db::enumValues(''));
    }
}
