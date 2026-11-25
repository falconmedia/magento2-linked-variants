<?php

/**
 * @package     FalconMedia_LinkedVariants
 * @author      Henk Valk (henk@falconmedia.nl)
 *
 * Copyright (c) Falcon Media (www.falconmedia.nl)
 */

declare(strict_types=1);

namespace FalconMedia\LinkedVariants\Model\ResourceModel;

use FalconMedia\LinkedVariants\Model\LinkType;
use Magento\Catalog\Model\ResourceModel\Product\Link;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Throwable;

/**
 * Reads and writes variant links of products, ordered by position.
 */
class VariantLink
{
    private ?int $positionAttributeId = null;

    private bool $positionAttributeResolved = false;

    public function __construct(private readonly Link $linkResource, private readonly LinkType $linkType)
    {
    }

    /**
     * Returns the linked product ids ordered by position.
     *
     * @return int[]
     */
    public function getLinkedIds(int $productId): array
    {
        return $this->getLinkedIdsByProduct([$productId])[$productId] ?? [];
    }

    /**
     * Returns the linked product ids per product, ordered by position. Products without links are left out.
     *
     * @param int[] $productIds
     * @return array<int, int[]>
     */
    public function getLinkedIdsByProduct(array $productIds): array
    {
        $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds))));
        $typeId = $this->linkType->getId();
        if ($productIds === [] || $typeId === null) {
            return [];
        }

        $connection = $this->linkResource->getConnection();
        $select = $connection->select()
            ->from(['l' => $this->linkResource->getMainTable()], ['product_id', 'linked_product_id'])
            ->where('l.product_id IN (?)', $productIds)
            ->where('l.link_type_id = ?', $typeId);

        $positionAttributeId = $this->getPositionAttributeId($typeId);
        if ($positionAttributeId !== null) {
            $select->joinLeft(
                ['p' => $this->linkResource->getAttributeTypeTable('int')],
                $connection->quoteInto(
                    'p.link_id = l.link_id AND p.product_link_attribute_id = ?',
                    $positionAttributeId
                ),
                []
            )->order(['p.value ASC', 'l.link_id ASC']);
        } else {
            $select->order('l.link_id ASC');
        }

        $links = [];
        foreach ($connection->fetchAll($select) as $row) {
            $links[(int) $row['product_id']][] = (int) $row['linked_product_id'];
        }

        return $links;
    }

    /**
     * Replaces the variant links of a product, keeping the given order as position.
     *
     * The core link resource only inserts and updates rows, so links that are no longer
     * wanted are removed here explicitly.
     *
     * @param int[] $linkedIds
     */
    public function saveLinkedIds(int $productId, array $linkedIds): void
    {
        $typeId = $this->linkType->requireId();
        $data = [];
        $position = 1;
        foreach ($linkedIds as $linkedId) {
            $linkedId = (int) $linkedId;
            if ($linkedId === $productId || isset($data[$linkedId])) {
                continue;
            }
            $data[$linkedId] = ['position' => $position++];
        }

        $connection = $this->linkResource->getConnection();
        $connection->beginTransaction();
        try {
            $deleteConditions = [
                'product_id = ?' => $productId,
                'link_type_id = ?' => $typeId,
            ];
            if ($data !== []) {
                $deleteConditions['linked_product_id NOT IN (?)'] = array_keys($data);
            }
            $connection->delete($this->linkResource->getMainTable(), $deleteConditions);

            $this->linkResource->saveProductLinks($productId, $data, $typeId);
            $connection->commit();
        } catch (Throwable $exception) {
            $connection->rollBack();
            throw $exception;
        }
    }

    /**
     * Connection of the link tables, for callers that wrap several saves in one transaction.
     */
    public function getConnection(): AdapterInterface
    {
        return $this->linkResource->getConnection();
    }

    private function getPositionAttributeId(int $typeId): ?int
    {
        if ($this->positionAttributeResolved) {
            return $this->positionAttributeId;
        }

        foreach ($this->linkResource->getAttributesByType($typeId) as $attribute) {
            if (($attribute['code'] ?? null) === 'position') {
                $this->positionAttributeId = (int) $attribute['id'];
                break;
            }
        }
        $this->positionAttributeResolved = true;

        return $this->positionAttributeId;
    }
}
