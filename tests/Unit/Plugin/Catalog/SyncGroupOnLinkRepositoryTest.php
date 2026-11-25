<?php

/**
 * @package     FalconMedia_LinkedVariants
 * @author      Henk Valk (henk@falconmedia.nl)
 *
 * Copyright (c) Falcon Media (www.falconmedia.nl)
 */

declare(strict_types=1);

namespace FalconMedia\LinkedVariants\Test\Unit\Plugin\Catalog;

use FalconMedia\LinkedVariants\Model\Group\Synchronizer;
use FalconMedia\LinkedVariants\Model\ResourceModel\VariantLink;
use FalconMedia\LinkedVariants\Plugin\Catalog\SyncGroupOnLinkRepository;
use Magento\Catalog\Api\Data\ProductLinkInterface;
use Magento\Catalog\Api\ProductLinkRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SyncGroupOnLinkRepositoryTest extends TestCase
{
    private VariantLink&MockObject $variantLink;

    private Synchronizer&MockObject $synchronizer;

    private LoggerInterface&MockObject $logger;

    private ProductLinkRepositoryInterface&MockObject $repository;

    private SyncGroupOnLinkRepository $plugin;

    protected function setUp(): void
    {
        $this->variantLink = $this->createMock(VariantLink::class);
        $this->synchronizer = $this->createMock(Synchronizer::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->repository = $this->createMock(ProductLinkRepositoryInterface::class);
        $productResource = $this->createMock(ProductResource::class);
        $productResource->method('getIdBySku')->willReturnMap([['A', 1], ['missing', false]]);
        $this->plugin = new SyncGroupOnLinkRepository(
            $this->variantLink,
            $this->synchronizer,
            $productResource,
            $this->logger
        );
    }

    private function link(string $sku, string $type): ProductLinkInterface&MockObject
    {
        $link = $this->createMock(ProductLinkInterface::class);
        $link->method('getSku')->willReturn($sku);
        $link->method('getLinkType')->willReturn($type);

        return $link;
    }

    public function testSaveSyncsWithTheLinksFromBeforeTheSave(): void
    {
        $link = $this->link('A', 'variant');
        $this->variantLink->expects($this->once())->method('getLinkedIds')->with(1)->willReturn([2]);
        $this->synchronizer->expects($this->once())->method('sync')->with(1, [2]);

        $this->plugin->beforeSave($this->repository, $link);
        $this->assertTrue($this->plugin->afterSave($this->repository, true, $link));
    }

    public function testDeleteSyncsWithTheLinksFromBeforeTheDelete(): void
    {
        $link = $this->link('A', 'variant');
        $this->variantLink->method('getLinkedIds')->with(1)->willReturn([2, 3]);
        $this->synchronizer->expects($this->once())->method('sync')->with(1, [2, 3]);

        $this->plugin->beforeDelete($this->repository, $link);
        $this->assertTrue($this->plugin->afterDelete($this->repository, true, $link));
    }

    public function testOtherLinkTypesAreIgnored(): void
    {
        $link = $this->link('A', 'related');
        $this->variantLink->expects($this->never())->method('getLinkedIds');
        $this->synchronizer->expects($this->never())->method('sync');

        $this->plugin->beforeSave($this->repository, $link);
        $this->plugin->afterSave($this->repository, true, $link);
    }

    public function testUnknownSkuIsIgnored(): void
    {
        $link = $this->link('missing', 'variant');
        $this->synchronizer->expects($this->never())->method('sync');

        $this->plugin->beforeSave($this->repository, $link);
        $this->plugin->afterSave($this->repository, true, $link);
    }

    public function testSyncFailureIsLoggedAndDoesNotBreakTheSave(): void
    {
        $link = $this->link('A', 'variant');
        $this->variantLink->method('getLinkedIds')->willReturn([]);
        $this->synchronizer->method('sync')->willThrowException(new \RuntimeException('db down'));
        $this->logger->expects($this->once())->method('error');

        $this->plugin->beforeSave($this->repository, $link);
        $this->assertTrue($this->plugin->afterSave($this->repository, true, $link));
    }
}
