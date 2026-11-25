<?php

/**
 * @package     FalconMedia_LinkedVariants
 * @author      Henk Valk (henk@falconmedia.nl)
 *
 * Copyright (c) Falcon Media (www.falconmedia.nl)
 */

declare(strict_types=1);

namespace FalconMedia\LinkedVariants\Setup\Patch\Data;

use FalconMedia\LinkedVariants\Model\LinkType;
use FalconMedia\LinkedVariants\Model\ResourceModel\LinkTypeStorage;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Registers the variant product link type with its position attribute.
 *
 * A "color_variant" link type from the module this one replaces is renamed in place, so its links and positions are
 * kept. When both types exist, the legacy links are merged into the variant type and the legacy type is removed.
 * Safe to run when neither, one or both types exist.
 */
class AddVariantLinkType implements DataPatchInterface
{
    public function __construct(private readonly LinkTypeStorage $storage)
    {
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function apply(): self
    {
        $variantId = $this->storage->findIdByCode(LinkType::CODE);
        $legacyId = $this->storage->findIdByCode(LinkType::LEGACY_CODE);

        if ($variantId === null && $legacyId !== null) {
            $this->storage->renameType($legacyId, LinkType::CODE);
            $variantId = $legacyId;
        } elseif ($variantId === null) {
            $variantId = $this->storage->insertType(LinkType::CODE);
        }

        // The position attribute must exist before a merge, otherwise moveLinks cannot carry the positions over.
        if ($this->storage->findAttributeId($variantId, 'position') === null) {
            $this->storage->insertAttribute($variantId, 'position', 'int');
        }

        if ($legacyId !== null && $legacyId !== $variantId) {
            $this->storage->moveLinks($legacyId, $variantId);
            $this->storage->deleteType($legacyId);
        }

        return $this;
    }
}
