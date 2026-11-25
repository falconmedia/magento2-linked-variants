<?php

/**
 * @package     FalconMedia_LinkedVariants
 * @author      Henk Valk (henk@falconmedia.nl)
 *
 * Copyright (c) Falcon Media (www.falconmedia.nl)
 */

declare(strict_types=1);

namespace FalconMedia\LinkedVariants\Test\Unit\Model\Group;

use FalconMedia\LinkedVariants\Model\Group\Resolver;
use FalconMedia\LinkedVariants\Model\Group\Synchronizer;
use FalconMedia\LinkedVariants\Model\ResourceModel\VariantLink;
use Magento\Catalog\Model\Product;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Indexer\CacheContext;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SynchronizerTest extends TestCase
{
    private VariantLink&MockObject $variantLink;

    private CacheContext&MockObject $cacheContext;

    private ManagerInterface&MockObject $eventManager;

    private AdapterInterface&MockObject $connection;

    private Synchronizer $synchronizer;

    protected function setUp(): void
    {
        $this->variantLink = $this->createMock(VariantLink::class);
        $this->cacheContext = $this->createMock(CacheContext::class);
        $this->eventManager = $this->createMock(ManagerInterface::class);
        $this->connection = $this->createMock(AdapterInterface::class);
        $this->variantLink->method('getConnection')->willReturn($this->connection);
        $this->synchronizer = new Synchronizer(
            new Resolver(),
            $this->variantLink,
            $this->cacheContext,
            $this->eventManager
        );
    }

    /**
     * @param array<int, int[]> $links
     */
    private function stubLinks(array $links): void
    {
        $this->variantLink->method('getLinkedIds')
            ->willReturnCallback(static fn (int $id): array => $links[$id] ?? []);
    }

    public function testNothingLinkedBeforeOrAfterDoesNothing(): void
    {
        $this->stubLinks([]);
        $this->variantLink->expects($this->never())->method('saveLinkedIds');
        $this->cacheContext->expects($this->never())->method('registerEntities');
        $this->eventManager->expects($this->never())->method('dispatch');
        $this->connection->expects($this->never())->method('beginTransaction');

        $this->synchronizer->sync(1, []);
    }

    public function testNewLinksAreMirroredOnTheLinkedProducts(): void
    {
        $this->stubLinks([1 => [2, 3]]);

        $saved = [];
        $this->variantLink->expects($this->exactly(2))
            ->method('saveLinkedIds')
            ->willReturnCallback(static function (int $id, array $ids) use (&$saved): void {
                $saved[$id] = $ids;
            });
        $this->cacheContext->expects($this->once())
            ->method('registerEntities')
            ->with(Product::CACHE_TAG, [2, 3]);
        $this->eventManager->expects($this->once())
            ->method('dispatch')
            ->with('clean_cache_by_tags', ['object' => $this->cacheContext]);
        $this->connection->expects($this->once())->method('beginTransaction');
        $this->connection->expects($this->once())->method('commit');
        $this->connection->expects($this->never())->method('rollBack');

        $this->synchronizer->sync(1, []);

        $this->assertSame([2 => [1, 3], 3 => [1, 2]], $saved);
    }

    public function testDroppedProductLosesTheGroup(): void
    {
        $this->stubLinks([1 => [2], 2 => [1, 3], 3 => [1, 2]]);

        $saved = [];
        $this->variantLink->expects($this->exactly(2))
            ->method('saveLinkedIds')
            ->willReturnCallback(static function (int $id, array $ids) use (&$saved): void {
                $saved[$id] = $ids;
            });
        $this->cacheContext->expects($this->once())
            ->method('registerEntities')
            ->with(Product::CACHE_TAG, [2, 3]);
        $this->eventManager->expects($this->once())->method('dispatch');

        $this->synchronizer->sync(1, [2, 3]);

        $this->assertSame([2 => [1], 3 => []], $saved);
    }

    public function testFailureRollsBackTheWholeGroupAndSkipsCacheCleaning(): void
    {
        $this->stubLinks([1 => [2, 3]]);

        $this->variantLink->expects($this->exactly(2))
            ->method('saveLinkedIds')
            ->willReturnCallback(static function (int $id): void {
                if ($id === 3) {
                    throw new \RuntimeException('db down');
                }
            });
        $this->connection->expects($this->once())->method('beginTransaction');
        $this->connection->expects($this->once())->method('rollBack');
        $this->connection->expects($this->never())->method('commit');
        $this->cacheContext->expects($this->never())->method('registerEntities');
        $this->eventManager->expects($this->never())->method('dispatch');

        $this->expectException(\RuntimeException::class);
        $this->synchronizer->sync(1, []);
    }
}
