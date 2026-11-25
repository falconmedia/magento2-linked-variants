<?php

/**
 * @package     FalconMedia_LinkedVariants
 * @author      Henk Valk (henk@falconmedia.nl)
 *
 * Copyright (c) Falcon Media (www.falconmedia.nl)
 */

declare(strict_types=1);

namespace FalconMedia\LinkedVariants\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * How a variant is shown in the row on the product page.
 */
class DisplayMode implements OptionSourceInterface
{
    public const IMAGE = 'image';
    public const SWATCH = 'swatch';
    public const TEXT = 'text';

    public const VALUES = [self::IMAGE, self::SWATCH, self::TEXT];

    /**
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::IMAGE, 'label' => __('Product image')],
            ['value' => self::SWATCH, 'label' => __('Swatch of the swatch attribute')],
            ['value' => self::TEXT, 'label' => __('Text only')],
        ];
    }
}
