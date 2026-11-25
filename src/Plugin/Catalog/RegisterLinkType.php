<?php

/**
 * @package     FalconMedia_LinkedVariants
 * @author      Henk Valk (henk@falconmedia.nl)
 *
 * Copyright (c) Falcon Media (www.falconmedia.nl)
 */

declare(strict_types=1);

namespace FalconMedia\LinkedVariants\Plugin\Catalog;

use FalconMedia\LinkedVariants\Model\LinkType;
use Magento\Catalog\Model\Product\LinkTypeProvider;

/**
 * Adds the variant link type next to related, up-sell and cross-sell, using the id stored in the database.
 */
class RegisterLinkType
{
    public function __construct(private readonly LinkType $linkType)
    {
    }

    /**
     * @param array<string, int> $result
     * @return array<string, int>
     */
    public function afterGetLinkTypes(LinkTypeProvider $subject, array $result): array
    {
        $id = $this->linkType->getId();
        if ($id !== null) {
            $result[LinkType::CODE] = $id;
        }

        return $result;
    }
}
