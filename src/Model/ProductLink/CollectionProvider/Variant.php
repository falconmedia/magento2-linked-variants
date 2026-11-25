<?php

/**
 * @package     FalconMedia_LinkedVariants
 * @author      Henk Valk (henk@falconmedia.nl)
 *
 * Copyright (c) Falcon Media (www.falconmedia.nl)
 */

declare(strict_types=1);

namespace FalconMedia\LinkedVariants\Model\ProductLink\CollectionProvider;

use FalconMedia\LinkedVariants\Model\LinkType;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\LinkFactory;
use Magento\Catalog\Model\ProductLink\CollectionProviderInterface;

/**
 * Exposes variant links to the product link repository and the admin product form.
 */
class Variant implements CollectionProviderInterface
{
    public function __construct(private readonly LinkFactory $linkFactory, private readonly LinkType $linkType)
    {
    }

    /**
     * @inheritDoc
     */
    public function getLinkedProducts(Product $product)
    {
        $collection = $this->linkFactory->create()
            ->setLinkTypeId($this->linkType->requireId())
            ->getProductCollection()
            ->setIsStrongMode();
        $collection->setProduct($product);
        $collection->setPositionOrder();

        /** @var Product[] $items */
        $items = $collection->getItems();

        return $items;
    }
}
