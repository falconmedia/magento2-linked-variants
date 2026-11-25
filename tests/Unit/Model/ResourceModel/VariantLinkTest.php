<?php

/**
 * @package     FalconMedia_LinkedVariants
 * @author      Henk Valk (henk@falconmedia.nl)
 *
 * Copyright (c) Falcon Media (www.falconmedia.nl)
 */

declare(strict_types=1);

namespace FalconMedia\LinkedVariants\Test\Unit\Model\ResourceModel;

use FalconMedia\LinkedVariants\Model\LinkType;
use FalconMedia\LinkedVariants\Model\ResourceModel\VariantLink;
use Magento\Catalog\Model\ResourceModel\Product\Link;
use Magento\Framework\DB\Adapter\AdapterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class VariantLinkTest extends TestCase
{
    private const TYPE_ID = 20;

    private Link&MockObject $linkResource;

    private AdapterInterface&MockObject $connection;

    private VariantLink $variantLink;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(AdapterInterface::class);
        $this->linkResource = $this->createMock(Link::class);
        $this->linkResource->method('getConnection')->willReturn($this->connection);
        $this->linkResource->method('getMainTable')->willReturn('catalog_product_link');
        $linkType = $this->createMock(LinkType::class);
        $linkType->method('requireId')->willReturn(self::TYPE_ID);
        $this->variantLink = new VariantLink($this->linkResource, $linkType);
    }

    public function testSaveDeletesRowsNotInTheListAndSavesPositions(): void
    {
        $this->connection->expects($this->once())->method('beginTransaction');
        $this->connection->expects($this->once())
            ->method('delete')
            ->with('catalog_product_link', [
                'product_id = ?' => 1,
                'link_type_id = ?' => self::TYPE_ID,
                'linked_product_id NOT IN (?)' => [5, 7],
            ]);
        $this->linkResource->expects($this->once())
            ->method('saveProductLinks')
            ->with(
                1,
                [
                    5 => ['position' => 1],
                    7 => ['position' => 2],
                ],
                self::TYPE_ID
            );
        $this->connection->expects($this->once())->method('commit');
        $this->connection->expects($this->never())->method('rollBack');

        $this->variantLink->saveLinkedIds(1, [5, 1, 7, 5]);
    }

    public function testSaveWithEmptyListDeletesAllLinks(): void
    {
        $this->connection->expects($this->once())
            ->method('delete')
            ->with('catalog_product_link', [
                'product_id = ?' => 1,
                'link_type_id = ?' => self::TYPE_ID,
            ]);
        $this->linkResource->expects($this->once())
            ->method('saveProductLinks')
            ->with(1, [], self::TYPE_ID);
        $this->connection->expects($this->once())->method('commit');

        $this->variantLink->saveLinkedIds(1, []);
    }

    public function testFailureRollsBackAndRethrows(): void
    {
        $this->connection->method('delete')->willThrowException(new \RuntimeException('db down'));
        $this->connection->expects($this->once())->method('rollBack');
        $this->connection->expects($this->never())->method('commit');

        $this->expectException(\RuntimeException::class);
        $this->variantLink->saveLinkedIds(1, [2]);
    }
}
