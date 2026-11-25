<?php

/**
 * @package     FalconMedia_LinkedVariants
 * @author      Henk Valk (henk@falconmedia.nl)
 *
 * Copyright (c) Falcon Media (www.falconmedia.nl)
 */

declare(strict_types=1);

namespace FalconMedia\LinkedVariants\Plugin\Catalog;

use FalconMedia\LinkedVariants\Model\Group\Synchronizer;
use FalconMedia\LinkedVariants\Model\LinkType;
use FalconMedia\LinkedVariants\Model\ResourceModel\VariantLink;
use Magento\Catalog\Api\Data\ProductLinkInterface;
use Magento\Catalog\Api\ProductLinkRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Keeps the variant group consistent when single links are saved or deleted through the product link repository.
 *
 * deleteById() is covered too: it calls delete() on the intercepted repository.
 */
class SyncGroupOnLinkRepository
{
    /**
     * Product id and its links before the call, one entry per running save or delete.
     *
     * @var array<int, array{int, int[]}>
     */
    private array $pending = [];

    public function __construct(
        private readonly VariantLink $variantLink,
        private readonly Synchronizer $synchronizer,
        private readonly ProductResource $productResource,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Remembers the links the product had before the save.
     *
     * @return null
     */
    public function beforeSave(ProductLinkRepositoryInterface $subject, ProductLinkInterface $entity)
    {
        $this->remember($entity);

        return null;
    }

    public function afterSave(ProductLinkRepositoryInterface $subject, bool $result, ProductLinkInterface $entity): bool
    {
        $this->syncRemembered($entity);

        return $result;
    }

    /**
     * Remembers the links the product had before the delete.
     *
     * @return null
     */
    public function beforeDelete(ProductLinkRepositoryInterface $subject, ProductLinkInterface $entity)
    {
        $this->remember($entity);

        return null;
    }

    public function afterDelete(
        ProductLinkRepositoryInterface $subject,
        bool $result,
        ProductLinkInterface $entity
    ): bool {
        $this->syncRemembered($entity);

        return $result;
    }

    private function remember(ProductLinkInterface $entity): void
    {
        if (!$this->isVariantLink($entity)) {
            return;
        }

        $productId = (int) $this->productResource->getIdBySku((string) $entity->getSku());
        $this->pending[] = [$productId, $productId > 0 ? $this->variantLink->getLinkedIds($productId) : []];
    }

    /**
     * Synchronises the group; a failure is logged and never breaks the saved link.
     */
    private function syncRemembered(ProductLinkInterface $entity): void
    {
        if (!$this->isVariantLink($entity) || $this->pending === []) {
            return;
        }

        [$productId, $previousIds] = array_pop($this->pending);
        if ($productId <= 0) {
            return;
        }

        try {
            $this->synchronizer->sync($productId, $previousIds);
        } catch (Throwable $exception) {
            $this->logger->error('Variant group sync failed: ' . $exception->getMessage(), [
                'product_id' => $productId,
                'exception' => $exception,
            ]);
        }
    }

    private function isVariantLink(ProductLinkInterface $entity): bool
    {
        return $entity->getLinkType() === LinkType::CODE;
    }
}
