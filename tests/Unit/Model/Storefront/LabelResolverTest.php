<?php

/**
 * @package     FalconMedia_LinkedVariants
 * @author      Henk Valk (henk@falconmedia.nl)
 *
 * Copyright (c) Falcon Media (www.falconmedia.nl)
 */

declare(strict_types=1);

namespace FalconMedia\LinkedVariants\Test\Unit\Model\Storefront;

use FalconMedia\LinkedVariants\Model\Storefront\LabelResolver;
use PHPUnit\Framework\TestCase;

class LabelResolverTest extends TestCase
{
    /**
     * @return array<string, array{?string, string, string}>
     */
    public static function cases(): array
    {
        return [
            'attribute wins' => ['Black', 'Boxing Gloves 2.0 - Pink', 'Black'],
            'suffix after dash' => [null, 'Boxing Gloves 2.0 - Black/White', 'Black/White'],
            'last dash wins' => ['', 'Boxing - Gloves - Ice', 'Ice'],
            'no dash falls back to name' => [null, 'Boxing Gloves', 'Boxing Gloves'],
            'empty suffix falls back to name' => [null, 'Boxing Gloves - ', 'Boxing Gloves -'],
            'whitespace attribute ignored' => ['  ', 'Boxing - Pink', 'Pink'],
            'suffix after en dash' => [
                null,
                "Training Shorts \u{2013} Black/Charcoal",
                'Black/Charcoal',
            ],
            'suffix after em dash' => [null, "Boxing Gloves \u{2014} Pink", 'Pink'],
            'last separator of any kind wins' => [null, "Boxing - Gloves \u{2013} Ice", 'Ice'],
            'hyphen after en dash wins' => [null, "Boxing \u{2013} Gloves - Black/White", 'Black/White'],
            'dash without spaces is not a separator' => [null, 'Boxing-Gloves', 'Boxing-Gloves'],
            'empty suffix after en dash falls back to name' => [
                null,
                "Boxing Gloves \u{2013} ",
                "Boxing Gloves \u{2013}",
            ],
        ];
    }

    /**
     * @dataProvider cases
     */
    public function testResolve(?string $attributeValue, string $name, string $expected): void
    {
        self::assertSame($expected, (new LabelResolver())->resolve($attributeValue, $name));
    }
}
