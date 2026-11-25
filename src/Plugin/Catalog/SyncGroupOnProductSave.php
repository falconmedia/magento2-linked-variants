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
use FalconMedia\LinkedVariants\Model\ResourceModel\VariantLink;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\Model\AbstractModel;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Keeps the variant group consistent whenever a product is saved.
 */
class SyncGroupOnProductSave
{
    private const PREVIOUS_IDS_KEY = 'linked_variants_previous_ids';

    public function __construct(
        private readonly VariantLink $variantLink,
        private readonly Synchronizer $synchronizer,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Remembers the links the product had before the save.
     *
     * @return AbstractModel[]
     */
    public function beforeSave(ProductResource $subject, AbstractModel $product): array
    {
        if ($product instanceof Product && $product->getId()) {
            $product->setData(self::PREVIOUS_IDS_KEY, $this->variantLink->getLinkedIds((int) $product->getId()));
        }

        return [$product];
    }

    /**
     * Synchronises the group after the save; a failure must never break the product save.
     */
    public function afterSave(ProductResource $subject, ProductResource $result, AbstractModel $product): ProductResource
    {
        if (!$product instanceof Product || !$product->getId()) {
            return $result;
        }

        $previousIds = $product->getData(self::PREVIOUS_IDS_KEY);
        $product->unsetData(self::PREVIOUS_IDS_KEY);

        try {
            $this->synchronizer->sync((int) $product->getId(), is_array($previousIds) ? $previousIds : []);
        } catch (Throwable $exception) {
            $this->logger->error('Variant group sync failed: ' . $exception->getMessage(), [
                'product_id' => $product->getId(),
                'exception' => $exception,
            ]);
        }

        return $result;
    }
}
