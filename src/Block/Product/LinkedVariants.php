<?php

/**
 * @package     FalconMedia_LinkedVariants
 * @author      Henk Valk (henk@falconmedia.nl)
 *
 * Copyright (c) Falcon Media (www.falconmedia.nl)
 */

declare(strict_types=1);

namespace FalconMedia\LinkedVariants\Block\Product;

use FalconMedia\LinkedVariants\ViewModel\Variants;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\View\Element\Template;

/**
 * Renders the linked variants on the product page. The data comes from the "view_model" argument.
 */
class LinkedVariants extends Template implements IdentityInterface
{
    /**
     * @return string[]
     */
    public function getIdentities(): array
    {
        $viewModel = $this->getData('view_model');

        return $viewModel instanceof Variants ? $viewModel->getIdentities() : [];
    }

    /**
     * @inheritDoc
     */
    protected function _toHtml()
    {
        $viewModel = $this->getData('view_model');
        if (!$viewModel instanceof Variants) {
            return '';
        }

        return count($viewModel->getVariants((string) $this->getData('image_id'))) > 1 ? parent::_toHtml() : '';
    }
}
