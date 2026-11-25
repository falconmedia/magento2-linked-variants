<?php

/**
 * @package     FalconMedia_LinkedVariants
 * @author      Henk Valk (henk@falconmedia.nl)
 *
 * Copyright (c) Falcon Media (www.falconmedia.nl)
 */

declare(strict_types=1);

namespace FalconMedia\LinkedVariants\Model;

use FalconMedia\LinkedVariants\Model\Config\Source\DisplayMode;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Reads the linked variants storefront configuration per store view.
 */
class Config
{
    private const XML_PATH_ENABLED = 'falconmedia_linked_variants/general/enabled';
    private const XML_PATH_TITLE = 'falconmedia_linked_variants/general/title';
    private const XML_PATH_LABEL_ATTRIBUTE = 'falconmedia_linked_variants/general/label_attribute';
    private const XML_PATH_DISPLAY_MODE = 'falconmedia_linked_variants/general/display_mode';
    private const XML_PATH_SWATCH_ATTRIBUTE = 'falconmedia_linked_variants/general/swatch_attribute';
    private const XML_PATH_SHOW_OUT_OF_STOCK = 'falconmedia_linked_variants/general/show_out_of_stock';
    private const XML_PATH_OUT_OF_STOCK_TEXT = 'falconmedia_linked_variants/general/out_of_stock_text';

    public function __construct(private readonly ScopeConfigInterface $scopeConfig)
    {
    }

    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Returns the configured title, or an empty string when the default title applies.
     */
    public function getTitle(?int $storeId = null): string
    {
        return $this->getString(self::XML_PATH_TITLE, $storeId);
    }

    public function getLabelAttribute(?int $storeId = null): string
    {
        return $this->getString(self::XML_PATH_LABEL_ATTRIBUTE, $storeId);
    }

    /**
     * Returns one of the DisplayMode values.
     */
    public function getDisplayMode(?int $storeId = null): string
    {
        $mode = $this->getString(self::XML_PATH_DISPLAY_MODE, $storeId);

        return in_array($mode, DisplayMode::VALUES, true) ? $mode : DisplayMode::IMAGE;
    }

    /**
     * Returns the attribute that provides the swatch; the label attribute when none is configured.
     */
    public function getSwatchAttribute(?int $storeId = null): string
    {
        $code = $this->getString(self::XML_PATH_SWATCH_ATTRIBUTE, $storeId);

        return $code !== '' ? $code : $this->getLabelAttribute($storeId);
    }

    public function showOutOfStock(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_SHOW_OUT_OF_STOCK,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Returns the configured out of stock text, or an empty string when the default text applies.
     */
    public function getOutOfStockText(?int $storeId = null): string
    {
        return $this->getString(self::XML_PATH_OUT_OF_STOCK_TEXT, $storeId);
    }

    private function getString(string $path, ?int $storeId): string
    {
        return trim((string) $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId));
    }
}
