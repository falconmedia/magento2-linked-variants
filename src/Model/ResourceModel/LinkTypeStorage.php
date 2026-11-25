<?php

/**
 * @package     FalconMedia_LinkedVariants
 * @author      Henk Valk (henk@falconmedia.nl)
 *
 * Copyright (c) Falcon Media (www.falconmedia.nl)
 */

declare(strict_types=1);

namespace FalconMedia\LinkedVariants\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;

/**
 * Reads and writes the product link type tables for the variant link type.
 */
class LinkTypeStorage
{
    public function __construct(private readonly ResourceConnection $resource)
    {
    }

    public function findIdByCode(string $code): ?int
    {
        $connection = $this->getConnection();
        $id = $connection->fetchOne(
            $connection->select()
                ->from($this->resource->getTableName('catalog_product_link_type'), 'link_type_id')
                ->where('code = ?', $code)
        );

        return $id === false || $id === null ? null : (int) $id;
    }

    /**
     * Creates the link type and returns its generated id.
     */
    public function insertType(string $code): int
    {
        $this->getConnection()->insert($this->resource->getTableName('catalog_product_link_type'), ['code' => $code]);

        return (int) $this->findIdByCode($code);
    }

    public function renameType(int $typeId, string $code): void
    {
        $this->getConnection()->update(
            $this->resource->getTableName('catalog_product_link_type'),
            ['code' => $code],
            ['link_type_id = ?' => $typeId]
        );
    }

    /**
     * Deletes a link type; links and attributes of that type are removed by the foreign keys.
     */
    public function deleteType(int $typeId): void
    {
        $this->getConnection()->delete(
            $this->resource->getTableName('catalog_product_link_type'),
            ['link_type_id = ?' => $typeId]
        );
    }

    public function findAttributeId(int $typeId, string $attributeCode): ?int
    {
        $connection = $this->getConnection();
        $id = $connection->fetchOne(
            $connection->select()
                ->from($this->resource->getTableName('catalog_product_link_attribute'), 'product_link_attribute_id')
                ->where('link_type_id = ?', $typeId)
                ->where('product_link_attribute_code = ?', $attributeCode)
        );

        return $id === false || $id === null ? null : (int) $id;
    }

    public function insertAttribute(int $typeId, string $attributeCode, string $dataType): void
    {
        $this->getConnection()->insert($this->resource->getTableName('catalog_product_link_attribute'), [
            'link_type_id' => $typeId,
            'product_link_attribute_code' => $attributeCode,
            'data_type' => $dataType,
        ]);
    }

    /**
     * Moves the links of one type to another, skipping pairs that already exist there, and carries over positions.
     */
    public function moveLinks(int $fromTypeId, int $toTypeId): void
    {
        $connection = $this->getConnection();
        $linkTable = $this->resource->getTableName('catalog_product_link');

        $linkIds = $connection->fetchCol(
            $connection->select()
                ->from(['l' => $linkTable], 'link_id')
                ->joinLeft(
                    ['t' => $linkTable],
                    $connection->quoteInto(
                        't.link_type_id = ? AND t.product_id = l.product_id'
                        . ' AND t.linked_product_id = l.linked_product_id',
                        $toTypeId
                    ),
                    []
                )
                ->where('l.link_type_id = ?', $fromTypeId)
                ->where('t.link_id IS NULL')
        );
        if ($linkIds === []) {
            return;
        }

        $fromPosition = $this->findAttributeId($fromTypeId, 'position');
        $toPosition = $this->findAttributeId($toTypeId, 'position');
        if ($fromPosition !== null && $toPosition !== null) {
            $connection->update(
                $this->resource->getTableName('catalog_product_link_attribute_int'),
                ['product_link_attribute_id' => $toPosition],
                ['product_link_attribute_id = ?' => $fromPosition, 'link_id IN (?)' => $linkIds]
            );
        }
        $connection->update($linkTable, ['link_type_id' => $toTypeId], ['link_id IN (?)' => $linkIds]);
    }

    private function getConnection(): AdapterInterface
    {
        return $this->resource->getConnection();
    }
}
