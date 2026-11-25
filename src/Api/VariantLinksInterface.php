<?php

/**
 * @package     FalconMedia_LinkedVariants
 * @author      Henk Valk (henk@falconmedia.nl)
 *
 * Copyright (c) Falcon Media (www.falconmedia.nl)
 */

declare(strict_types=1);

namespace FalconMedia\LinkedVariants\Api;

/**
 * Reads the product links of the variant link type (code "variant"), for use by other modules.
 *
 * Variant links are symmetric and transitive: every product of a variant group is linked to all the others.
 */
interface VariantLinksInterface
{
    /**
     * Returns the linked product ids per product, ordered by position. Products without links are left out.
     *
     * @param int[] $productIds
     * @return array<int, int[]>
     */
    public function getVariantLinks(array $productIds): array;

    /**
     * Returns the linked product ids of one product, ordered by position.
     *
     * @return int[]
     */
    public function getLinkedProductIds(int $productId): array;
}
