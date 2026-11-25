<?php

/**
 * @package     FalconMedia_LinkedVariants
 * @author      Henk Valk (henk@falconmedia.nl)
 *
 * Copyright (c) Falcon Media (www.falconmedia.nl)
 */

declare(strict_types=1);

namespace FalconMedia\LinkedVariants\Model;

use FalconMedia\LinkedVariants\Api\VariantLinksInterface;
use FalconMedia\LinkedVariants\Model\ResourceModel\VariantLink;

/**
 * Default implementation of the variant link reader.
 */
class VariantLinks implements VariantLinksInterface
{
    public function __construct(private readonly VariantLink $variantLink)
    {
    }

    /**
     * @inheritDoc
     */
    public function getVariantLinks(array $productIds): array
    {
        return $this->variantLink->getLinkedIdsByProduct($productIds);
    }

    /**
     * @inheritDoc
     */
    public function getLinkedProductIds(int $productId): array
    {
        return $this->variantLink->getLinkedIds($productId);
    }
}
