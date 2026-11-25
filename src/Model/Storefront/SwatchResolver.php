<?php

/**
 * @package     FalconMedia_LinkedVariants
 * @author      Henk Valk (henk@falconmedia.nl)
 *
 * Copyright (c) Falcon Media (www.falconmedia.nl)
 */

declare(strict_types=1);

namespace FalconMedia\LinkedVariants\Model\Storefront;

use Magento\Swatches\Helper\Data as SwatchHelper;
use Magento\Swatches\Helper\Media as SwatchMedia;
use Magento\Swatches\Model\Swatch;

/**
 * Turns the option of a swatch attribute into something a template can render: a colour or an image URL.
 */
class SwatchResolver
{
    public const NONE = '';
    public const COLOR = 'color';
    public const IMAGE = 'image';

    private const IMAGE_TYPE = 'swatch_thumb';

    public function __construct(private readonly SwatchHelper $swatchHelper, private readonly SwatchMedia $swatchMedia)
    {
    }

    /**
     * Returns the swatch of an option; multiple select values use their first option.
     *
     * @param mixed $optionValue raw attribute value of a product: an option id or a comma separated list of ids
     * @return array{type: string, value: string}
     */
    public function resolve(mixed $optionValue): array
    {
        $none = ['type' => self::NONE, 'value' => ''];
        $optionId = (int) strtok(is_scalar($optionValue) ? (string) $optionValue : '', ',');
        if ($optionId <= 0) {
            return $none;
        }

        $swatch = $this->swatchHelper->getSwatchesByOptionsId([$optionId])[$optionId] ?? null;
        if (!is_array($swatch)) {
            return $none;
        }

        $value = trim((string) ($swatch['value'] ?? ''));
        $type = (int) ($swatch['type'] ?? Swatch::SWATCH_TYPE_EMPTY);
        if ($value === '') {
            return $none;
        }

        if ($type === Swatch::SWATCH_TYPE_VISUAL_COLOR) {
            return preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{4}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $value) === 1
                ? ['type' => self::COLOR, 'value' => $value]
                : $none;
        }
        if ($type === Swatch::SWATCH_TYPE_VISUAL_IMAGE) {
            return ['type' => self::IMAGE, 'value' => (string) $this->swatchMedia->getSwatchAttributeImage(
                self::IMAGE_TYPE,
                $value
            )];
        }

        return $none;
    }
}
