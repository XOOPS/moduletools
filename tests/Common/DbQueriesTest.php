<?php

declare(strict_types=1);

namespace {
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
    }
}
