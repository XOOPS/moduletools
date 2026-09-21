<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Tests\Migration;

use PHPUnit\Framework\TestCase;
use Xmf\Pagination\PaginatedResult;
use Xoops\ModuleTools\Common\PaginationState;

final class PaginationNonParityTest extends TestCase
{
    public function testZeroResultContractIsNotEquivalent(): void
    {
        if (!class_exists(PaginatedResult::class)) {
            self::markTestSkipped('Xmf\Pagination requires xoops/xmf >= 1.5');
        }
        $legacy = new PaginationState(total: 0);
        $target = PaginatedResult::empty();

        self::assertSame(1, $legacy->pageCount());
        self::assertSame(0, $target->totalPages());
        self::assertNotSame($legacy->pageCount(), $target->totalPages());
    }

    public function testLegacyBandAndSqlContractsHaveNoPaginatedResultEquivalent(): void
    {
        $legacy = new PaginationState(total: 101, limit: 20, currentPage: 6);

        self::assertSame([1, 2, 3, 4, 5, 6], $legacy->pages());
        self::assertSame(' LIMIT 100, 20', $legacy->limitClause());
        self::assertFalse(method_exists(PaginatedResult::class, 'pages'));
        self::assertFalse(method_exists(PaginatedResult::class, 'limitClause'));
    }
}
