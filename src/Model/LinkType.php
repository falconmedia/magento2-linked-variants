<?php

/**
 * @package     FalconMedia_LinkedVariants
 * @author      Henk Valk (henk@falconmedia.nl)
 *
 * Copyright (c) Falcon Media (www.falconmedia.nl)
 */

declare(strict_types=1);

namespace FalconMedia\LinkedVariants\Model;

use FalconMedia\LinkedVariants\Model\ResourceModel\LinkTypeStorage;
use Magento\Framework\Exception\LocalizedException;

/**
 * Identifies the variant product link type. The numeric id is assigned by the database, so it is looked up by code.
 */
class LinkType
{
    public const CODE = 'variant';

    /**
     * Code used by the module this one replaces; the data patch renames it to CODE.
     */
    public const LEGACY_CODE = 'color_variant';

    private ?int $id = null;

    public function __construct(private readonly LinkTypeStorage $storage)
    {
    }

    /**
     * Returns the link type id, or null while the data patch has not created the link type yet.
     */
    public function getId(): ?int
    {
        if ($this->id === null) {
            $this->id = $this->storage->findIdByCode(self::CODE);
        }

        return $this->id;
    }

    /**
     * @throws LocalizedException when the link type does not exist
     */
    public function requireId(): int
    {
        $id = $this->getId();
        if ($id === null) {
            throw new LocalizedException(__('Product link type "%1" does not exist. Run setup:upgrade.', self::CODE));
        }

        return $id;
    }
}
