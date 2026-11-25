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
 * Derives the variant label shown under a variant thumbnail.
 *
 * Uses the configured attribute value when present, otherwise the part of the
 * product name after the last separator (a hyphen, en dash or em dash surrounded by spaces, as in
 * "Boxing Gloves - Black").
 */
class LabelResolver
{
    /**
     * Hyphen, en dash and em dash, each surrounded by spaces.
     */
    private const SEPARATORS = [' - ', " \u{2013} ", " \u{2014} "];

    public function resolve(?string $attributeValue, string $name): string
    {
        $attributeValue = trim((string) $attributeValue);
        if ($attributeValue !== '') {
            return $attributeValue;
        }

        $name = trim($name);
        $position = false;
        $length = 0;
        foreach (self::SEPARATORS as $separator) {
            $found = strrpos($name, $separator);
            if ($found !== false && ($position === false || $found > $position)) {
                $position = $found;
                $length = strlen($separator);
            }
        }

        if ($position !== false) {
            $suffix = trim(substr($name, $position + $length));
            if ($suffix !== '') {
                return $suffix;
            }
        }

        return $name;
    }
}
