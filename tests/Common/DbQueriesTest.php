<?php

declare(strict_types=1);

namespace {
    // The language constant of XOOPS core (language/english/global.php).
    if (!defined('_DB_QUERY_ERROR')) {
        define('_DB_QUERY_ERROR', 'Error executing query: %s ');
    }

    if (!class_exists('XoopsMySQLDatabase')) {
        class XoopsMySQLDatabase
        {
            public function prefix($table) { return 'xoops_' . $table; }
            public function quote($value) { return "'" . str_replace("'", "''", $value) . "'"; }
            public function query($sql) {}
            public function exec($sql) {}
            public function isResultSet($result) { return $result instanceof mysqli_result; }
            public function fetchArray($result) {}
            public function fetchBoth($result) {}
            public function getInsertId() { return 42; }
            public function getRowsNum($result) { return 1; }
            public function error() { return 'insert failed'; }
        }
    }
}

namespace Xoops\ModuleTools\Tests\Common {
    use PHPUnit\Framework\TestCase;
    use Xoops\ModuleTools\Common\Db;

    final class DbQueriesTest extends TestCase
    {
        public function testClonePreservesNullAndOtherValues(): void
        {
            $result = $this->createMock(\mysqli_result::class);
            $db = $this->getMockBuilder(\XoopsMySQLDatabase::class)->onlyMethods(['query', 'exec', 'fetchArray'])->getMock();
            $db->method('query')->willReturn($result);
            $db->method('fetchArray')->willReturn(['id' => 1, 'nullable' => null, 'blank' => '', 'zero' => 0, 'text' => "it's"]);
            $db->expects(self::once())->method('exec')->with("INSERT INTO xoops_items (`nullable`, `blank`, `zero`, `text`) VALUES (NULL, '', '0', 'it''s')")->willReturn(true);
            self::assertSame(42, Db::cloneRecord($db, 'items', 'id', 1));
        }

        public function testMetadataQueryIsScopedToCurrentSchema(): void
        {
            $result = $this->createMock(\mysqli_result::class);
            $db = $this->getMockBuilder(\XoopsMySQLDatabase::class)->onlyMethods(['query', 'fetchBoth'])->getMock();
            $db->expects(self::once())->method('query')->with("SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'xoops_items' AND COLUMN_NAME = 'status'")->willReturn($result);
            $db->method('fetchBoth')->willReturn(['COLUMN_TYPE' => "enum('draft','live')"]);
            self::assertSame(['draft', 'live'], Db::enumerate($db, 'items', 'status'));
        }

        public function testCloneThrowsWhenTheInsertFails(): void
        {
            $result = $this->createMock(\mysqli_result::class);
            $db = $this->getMockBuilder(\XoopsMySQLDatabase::class)->onlyMethods(['query', 'exec', 'fetchArray'])->getMock();
            $db->method('query')->willReturn($result);
            $db->method('fetchArray')->willReturn(['id' => 1, 'text' => 'x']);
            $db->method('exec')->willReturn(false);

            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('insert failed');
            Db::cloneRecord($db, 'items', 'id', 1);
        }

        public function testEnumerateThrowsWhenTheQueryFails(): void
        {
            $db = $this->getMockBuilder(\XoopsMySQLDatabase::class)->onlyMethods(['query'])->getMock();
            $db->method('query')->willReturn(false);

            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('insert failed');
            Db::enumerate($db, 'items', 'status');
        }

        public function testNameWithATrailingLineFeedIsRefusedBeforeAnyQuery(): void
        {
            $db = $this->getMockBuilder(\XoopsMySQLDatabase::class)->onlyMethods(['query', 'exec'])->getMock();
            $db->expects(self::never())->method('query');
            $db->expects(self::never())->method('exec');

            self::assertFalse(Db::fieldExists($db, "id\n", 'xoops_items'));
            self::assertFalse(Db::fieldExists($db, 'id', "xoops_items\n"));
            self::assertFalse(Db::cloneRecord($db, "items\n", 'id', 1));
            self::assertFalse(Db::cloneRecord($db, 'items', "id\n", 1));
            self::assertSame([], Db::enumerate($db, "items\n", 'status'));
            self::assertSame([], Db::enumerate($db, 'items', "status\n"));
        }

        public function testFieldExistsStillTakesABacktickedTableName(): void
        {
            $result = $this->createMock(\mysqli_result::class);
            $db = $this->getMockBuilder(\XoopsMySQLDatabase::class)->onlyMethods(['query'])->getMock();
            $db->expects(self::once())->method('query')->with("SHOW COLUMNS FROM `xoops_items` LIKE 'id'")->willReturn($result);

            self::assertTrue(Db::fieldExists($db, 'id', '`xoops_items`'));
        }
    }
}
