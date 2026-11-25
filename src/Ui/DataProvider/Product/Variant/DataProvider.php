<?php

/**
 * @package     FalconMedia_LinkedVariants
 * @author      Henk Valk (henk@falconmedia.nl)
 *
 * Copyright (c) Falcon Media (www.falconmedia.nl)
 */

declare(strict_types=1);

namespace FalconMedia\LinkedVariants\Ui\DataProvider\Product\Variant;

use FalconMedia\LinkedVariants\Model\LinkType;
use Magento\Catalog\Ui\DataProvider\Product\Related\AbstractDataProvider;

/**
 * Feeds the variant product grid in the admin product form.
 */
class DataProvider extends AbstractDataProvider
{
    /**
     * @inheritDoc
     */
    protected function getLinkType()
    {
        return LinkType::CODE;
    }
}
