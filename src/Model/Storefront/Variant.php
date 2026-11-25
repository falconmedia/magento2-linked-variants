<?php

/**
 * @package     FalconMedia_LinkedVariants
 * @author      Henk Valk (henk@falconmedia.nl)
 *
 * Copyright (c) Falcon Media (www.falconmedia.nl)
 */

declare(strict_types=1);

namespace FalconMedia\LinkedVariants\Model\Storefront;

/**
 * One variant as rendered on the product page.
 *
 * The swatch type is one of the SwatchResolver constants; the swatch value is a CSS colour or an image URL.
 */
class Variant
{
    public function __construct(
        public readonly int $id,
        public readonly string $label,
        public readonly string $url,
        public readonly string $imageUrl,
        public readonly int $imageWidth,
        public readonly int $imageHeight,
        public readonly bool $inStock,
        public readonly bool $isCurrent,
        public readonly string $swatchType = SwatchResolver::NONE,
        public readonly string $swatchValue = ''
    ) {
    }
}
