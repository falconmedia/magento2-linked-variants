<?php

/**
 * @package     FalconMedia_LinkedVariants
 * @author      Henk Valk (henk@falconmedia.nl)
 *
 * Copyright (c) Falcon Media (www.falconmedia.nl)
 */

declare(strict_types=1);

namespace FalconMedia\LinkedVariants\Test\Unit\Model;

use FalconMedia\LinkedVariants\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /**
     * @param array<string, string> $values config values by field id
     */
    private function config(array $values): Config
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(
            static fn (string $path): ?string => $values[substr($path, strlen('falconmedia_linked_variants/general/'))]
                ?? null
        );

        return new Config($scopeConfig);
    }

    public function testUnknownDisplayModeFallsBackToImage(): void
    {
        self::assertSame('image', $this->config(['display_mode' => 'carousel'])->getDisplayMode());
        self::assertSame('image', $this->config([])->getDisplayMode());
        self::assertSame('swatch', $this->config(['display_mode' => 'swatch'])->getDisplayMode());
        self::assertSame('text', $this->config(['display_mode' => 'text'])->getDisplayMode());
    }

    public function testSwatchAttributeFallsBackToLabelAttribute(): void
    {
        self::assertSame('size', $this->config(['label_attribute' => 'size'])->getSwatchAttribute());
        self::assertSame(
            'color',
            $this->config(['label_attribute' => 'size', 'swatch_attribute' => 'color'])->getSwatchAttribute()
        );
    }

    public function testTextsAreTrimmed(): void
    {
        $config = $this->config(['title' => '  Colours ', 'out_of_stock_text' => ' ']);

        self::assertSame('Colours', $config->getTitle());
        self::assertSame('', $config->getOutOfStockText());
    }
}
