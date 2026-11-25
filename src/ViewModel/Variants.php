<?php

/**
 * @package     FalconMedia_LinkedVariants
 * @author      Henk Valk (henk@falconmedia.nl)
 *
 * Copyright (c) Falcon Media (www.falconmedia.nl)
 */

declare(strict_types=1);

namespace FalconMedia\LinkedVariants\ViewModel;

use FalconMedia\LinkedVariants\Model\Config;
use FalconMedia\LinkedVariants\Model\ResourceModel\VariantLink;
use FalconMedia\LinkedVariants\Model\Storefront\Variant;
use FalconMedia\LinkedVariants\Model\Storefront\VariantProvider;
use Hyva\Theme\ViewModel\CurrentProduct;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Gives the product page template its variants, title and display settings.
 */
class Variants implements ArgumentInterface
{
    /** @var array<string, Variant[]> */
    private array $variants = [];

    public function __construct(
        private readonly CurrentProduct $currentProduct,
        private readonly Config $config,
        private readonly VariantProvider $variantProvider,
        private readonly VariantLink $variantLink,
        private readonly EavConfig $eavConfig
    ) {
    }

    public function getProduct(): ?Product
    {
        if (!$this->currentProduct->exists()) {
            return null;
        }
        $product = $this->currentProduct->get();

        return $product instanceof Product ? $product : null;
    }

    /**
     * @return Variant[]
     */
    public function getVariants(string $imageId = ''): array
    {
        $product = $this->getProduct();
        if (!$product) {
            return [];
        }

        $imageId = $imageId !== '' ? $imageId : VariantProvider::DEFAULT_IMAGE_ID;
        $key = $product->getId() . '|' . $product->getStoreId() . '|' . $imageId;
        if (!isset($this->variants[$key])) {
            $this->variants[$key] = $this->variantProvider->getVariants($product, $imageId);
        }

        return $this->variants[$key];
    }

    /**
     * The provider always puts the product being viewed first.
     */
    public function getCurrentVariant(string $imageId = ''): ?Variant
    {
        return $this->getVariants($imageId)[0] ?? null;
    }

    /**
     * Returns the configured title; without one the store label of the name attribute, else "Variant".
     */
    public function getTitle(): string
    {
        $storeId = $this->getStoreId();
        $title = $this->config->getTitle($storeId);
        if ($title !== '') {
            return $title;
        }

        $code = $this->config->getLabelAttribute($storeId);
        if ($code !== '') {
            $attribute = $this->eavConfig->getAttribute(Product::ENTITY, $code);
            $label = trim((string) $attribute->getStoreLabel($storeId));
            if ($attribute->getAttributeId() && $label !== '') {
                return $label;
            }
        }

        return (string) __('Variant');
    }

    public function getOutOfStockText(): string
    {
        $text = $this->config->getOutOfStockText($this->getStoreId());

        return $text !== '' ? $text : (string) __('(out of stock)');
    }

    /**
     * Returns one of the DisplayMode values.
     */
    public function getDisplayMode(): string
    {
        return $this->config->getDisplayMode($this->getStoreId());
    }

    /**
     * Cache tags of the product and all its linked products, so one change refreshes the whole group.
     *
     * Hidden linked products are tagged too, so the page refreshes when one becomes available again.
     *
     * @return string[]
     */
    public function getIdentities(): array
    {
        $product = $this->getProduct();
        if (!$product) {
            return [];
        }

        $identities = [Product::CACHE_TAG . '_' . $product->getId()];
        foreach ($this->variantLink->getLinkedIds((int) $product->getId()) as $linkedId) {
            $identities[] = Product::CACHE_TAG . '_' . $linkedId;
        }

        return array_values(array_unique($identities));
    }

    private function getStoreId(): ?int
    {
        $product = $this->getProduct();

        return $product ? (int) $product->getStoreId() : null;
    }
}
