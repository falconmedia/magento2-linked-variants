<?php

/**
 * @package     FalconMedia_LinkedVariants
 * @author      Henk Valk (henk@falconmedia.nl)
 *
 * Copyright (c) Falcon Media (www.falconmedia.nl)
 */

declare(strict_types=1);

namespace FalconMedia\LinkedVariants\Test\Unit\Model\Storefront;

use FalconMedia\LinkedVariants\Model\Storefront\SwatchResolver;
use Magento\Swatches\Helper\Data as SwatchHelper;
use Magento\Swatches\Helper\Media as SwatchMedia;
use Magento\Swatches\Model\Swatch;
use PHPUnit\Framework\TestCase;

class SwatchResolverTest extends TestCase
{
    /**
     * @param array<int, array<string, mixed>> $swatches swatch data by option id
     */
    private function resolver(array $swatches): SwatchResolver
    {
        $helper = $this->createMock(SwatchHelper::class);
        $helper->method('getSwatchesByOptionsId')->willReturn($swatches);
        $media = $this->createMock(SwatchMedia::class);
        $media->method('getSwatchAttributeImage')->willReturnCallback(
            static fn (string $type, string $file): string => 'https://example.test/media/' . $type . $file
        );

        return new SwatchResolver($helper, $media);
    }

    public function testColorSwatch(): void
    {
        $swatch = ['type' => Swatch::SWATCH_TYPE_VISUAL_COLOR, 'value' => '#1a2B3c'];

        self::assertSame(
            ['type' => 'color', 'value' => '#1a2B3c'],
            $this->resolver([7 => $swatch])->resolve('7')
        );
    }

    public function testInvalidColorIsRejected(): void
    {
        $swatch = ['type' => Swatch::SWATCH_TYPE_VISUAL_COLOR, 'value' => 'red;background:url(x)'];

        self::assertSame(['type' => '', 'value' => ''], $this->resolver([7 => $swatch])->resolve(7));
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function colorLengths(): array
    {
        return [
            'three digits' => ['#abc', true],
            'four digits' => ['#abcd', true],
            'six digits' => ['#AABBCC', true],
            'eight digits' => ['#aabbccdd', true],
            'five digits' => ['#abcde', false],
            'seven digits' => ['#abcdeff', false],
            'nine digits' => ['#aabbccdde', false],
            'no hash' => ['aabbcc', false],
        ];
    }

    /**
     * @dataProvider colorLengths
     */
    public function testOnlyValidHexLengthsAreColors(string $value, bool $valid): void
    {
        $swatch = ['type' => Swatch::SWATCH_TYPE_VISUAL_COLOR, 'value' => $value];
        $expected = $valid ? ['type' => 'color', 'value' => $value] : ['type' => '', 'value' => ''];

        self::assertSame($expected, $this->resolver([7 => $swatch])->resolve(7));
    }

    public function testImageSwatch(): void
    {
        $swatch = ['type' => Swatch::SWATCH_TYPE_VISUAL_IMAGE, 'value' => '/a/b.png'];

        self::assertSame(
            ['type' => 'image', 'value' => 'https://example.test/media/swatch_thumb/a/b.png'],
            $this->resolver([7 => $swatch])->resolve(7)
        );
    }

    public function testTextSwatchHasNoVisual(): void
    {
        $swatch = ['type' => Swatch::SWATCH_TYPE_TEXTUAL, 'value' => 'XL'];

        self::assertSame(['type' => '', 'value' => ''], $this->resolver([7 => $swatch])->resolve(7));
    }

    public function testMissingOrEmptyValues(): void
    {
        $resolver = $this->resolver([]);

        self::assertSame(['type' => '', 'value' => ''], $resolver->resolve(null));
        self::assertSame(['type' => '', 'value' => ''], $resolver->resolve(''));
        self::assertSame(['type' => '', 'value' => ''], $resolver->resolve('9'));
    }

    public function testMultiselectUsesFirstOption(): void
    {
        $swatch = ['type' => Swatch::SWATCH_TYPE_VISUAL_COLOR, 'value' => '#fff'];

        self::assertSame(
            ['type' => 'color', 'value' => '#fff'],
            $this->resolver([7 => $swatch])->resolve('7,9')
        );
    }
}
