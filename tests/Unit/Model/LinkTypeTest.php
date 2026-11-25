<?php

/**
 * @package     FalconMedia_LinkedVariants
 * @author      Henk Valk (henk@falconmedia.nl)
 *
 * Copyright (c) Falcon Media (www.falconmedia.nl)
 */

declare(strict_types=1);

namespace FalconMedia\LinkedVariants\Test\Unit\Model;

use FalconMedia\LinkedVariants\Model\LinkType;
use FalconMedia\LinkedVariants\Model\ResourceModel\LinkTypeStorage;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\TestCase;

class LinkTypeTest extends TestCase
{
    public function testIdIsLookedUpByCodeOnce(): void
    {
        $storage = $this->createMock(LinkTypeStorage::class);
        $storage->expects($this->once())->method('findIdByCode')->with('variant')->willReturn(20);

        $linkType = new LinkType($storage);

        self::assertSame(20, $linkType->getId());
        self::assertSame(20, $linkType->requireId());
    }

    public function testMissingTypeGivesNullAndRequireThrows(): void
    {
        $storage = $this->createMock(LinkTypeStorage::class);
        $storage->method('findIdByCode')->willReturn(null);

        $linkType = new LinkType($storage);

        self::assertNull($linkType->getId());
        $this->expectException(LocalizedException::class);
        $linkType->requireId();
    }
}
