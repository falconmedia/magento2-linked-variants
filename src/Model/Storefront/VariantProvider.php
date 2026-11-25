<?php

/**
 * @package     FalconMedia_LinkedVariants
 * @author      Henk Valk (henk@falconmedia.nl)
 *
 * Copyright (c) Falcon Media (www.falconmedia.nl)
 */

declare(strict_types=1);

namespace FalconMedia\LinkedVariants\Model\Storefront;

use FalconMedia\LinkedVariants\Model\Config;
use FalconMedia\LinkedVariants\Model\Config\Source\DisplayMode;
use FalconMedia\LinkedVariants\Model\ResourceModel\VariantLink;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogInventory\Model\ResourceModel\Stock\Status as StockStatus;
use Magento\Eav\Model\Config as EavConfig;

/**
 * Builds the list of variants for a product: the product itself first, then its links in position order.
 */
class VariantProvider
{
    public const DEFAULT_IMAGE_ID = 'product_page_image_small';

    public function __construct(
        private readonly Config $config,
        private readonly VariantLink $variantLink,
        private readonly CollectionFactory $collectionFactory,
        private readonly Visibility $visibility,
        private readonly ImageHelper $imageHelper,
        private readonly LabelResolver $labelResolver,
        private readonly SwatchResolver $swatchResolver,
        private readonly EavConfig $eavConfig,
        private readonly StockStatus $stockStatus
    ) {
    }

    /**
     * @return Variant[]
     */
    public function getVariants(Product $product, string $imageId = self::DEFAULT_IMAGE_ID): array
    {
        $storeId = (int) $product->getStoreId();
        if (!$this->config->isEnabled($storeId)) {
            return [];
        }

        $linkedIds = $this->variantLink->getLinkedIds((int) $product->getId());
        if ($linkedIds === []) {
            return [];
        }

        $labelAttribute = $this->resolveAttribute($this->config->getLabelAttribute($storeId));
        $swatchAttribute = $this->config->getDisplayMode($storeId) === DisplayMode::SWATCH
            ? $this->resolveAttribute($this->config->getSwatchAttribute($storeId))
            : '';
        $showOutOfStock = $this->config->showOutOfStock($storeId);

        $variants = [$this->createVariant($product, $labelAttribute, $swatchAttribute, $imageId, true)];
        $linked = $this->loadLinkedProducts(
            $linkedIds,
            $storeId,
            array_filter([$labelAttribute, $swatchAttribute]),
            $showOutOfStock
        );
        foreach ($linkedIds as $linkedId) {
            if (!isset($linked[$linkedId])) {
                continue;
            }
            $variants[] = $this->createVariant($linked[$linkedId], $labelAttribute, $swatchAttribute, $imageId, false);
        }

        return count($variants) > 1 ? $variants : [];
    }

    /**
     * @param int[] $ids
     * @param string[] $extraAttributes
     * @return array<int, Product>
     */
    private function loadLinkedProducts(array $ids, int $storeId, array $extraAttributes, bool $includeOutOfStock): array
    {
        $attributes = array_merge(
            ['name', 'small_image', 'thumbnail', 'image', 'url_key', 'status', 'visibility'],
            $extraAttributes
        );

        $collection = $this->collectionFactory->create()
            ->setStoreId($storeId)
            ->addAttributeToSelect($attributes)
            ->addIdFilter($ids)
            ->addAttributeToFilter('status', ['eq' => Status::STATUS_ENABLED])
            ->setVisibility($this->visibility->getVisibleInSiteIds())
            ->addStoreFilter($storeId)
            ->addUrlRewrite();
        // Joins is_salable once for all variants and, when out of stock variants are hidden, filters them in SQL.
        // The flag stops CatalogInventory from adding its own filter.
        $this->stockStatus->addStockDataToCollection($collection, !$includeOutOfStock);
        $collection->setFlag('has_stock_status_filter', true);

        $result = [];
        foreach ($collection as $item) {
            $result[(int) $item->getId()] = $item;
        }

        return $result;
    }

    private function createVariant(
        Product $product,
        string $labelAttribute,
        string $swatchAttribute,
        string $imageId,
        bool $isCurrent
    ): Variant {
        // The helper creates the resized cache file when it is missing, so a tile never points to a 404 or 500.
        $image = $this->imageHelper->init($product, $imageId);
        $imageUrl = (string) $image->getUrl();
        $imageWidth = (int) $image->getWidth();
        $imageHeight = (int) $image->getHeight();
        $attributeValue = $labelAttribute !== '' ? $this->getAttributeText($product, $labelAttribute) : null;
        $swatch = $swatchAttribute !== ''
            ? $this->swatchResolver->resolve($product->getData($swatchAttribute))
            : ['type' => SwatchResolver::NONE, 'value' => ''];

        return new Variant(
            (int) $product->getId(),
            $this->labelResolver->resolve($attributeValue, (string) $product->getName()),
            (string) $product->getProductUrl(),
            $imageUrl,
            $imageWidth,
            $imageHeight,
            $isCurrent ? true : $this->isSalable($product),
            $isCurrent,
            $swatch['type'],
            $swatch['value']
        );
    }

    private function isSalable(Product $product): bool
    {
        return $product->hasData('is_salable')
            ? (bool) $product->getData('is_salable')
            : (bool) $product->isSalable();
    }

    /**
     * Returns the attribute code when it is a product attribute, or an empty string.
     */
    private function resolveAttribute(string $code): string
    {
        if ($code === '') {
            return '';
        }

        return $this->eavConfig->getAttribute(Product::ENTITY, $code)->getAttributeId() ? $code : '';
    }

    private function getAttributeText(Product $product, string $attributeCode): ?string
    {
        $attribute = $this->eavConfig->getAttribute(Product::ENTITY, $attributeCode);
        if (!$attribute->getAttributeId()) {
            return null;
        }
        $text = $attribute->getFrontend()->getValue($product);
        if (is_array($text)) {
            $text = implode(', ', $text);
        }

        return $text === null || $text === false ? null : (string) $text;
    }
}
