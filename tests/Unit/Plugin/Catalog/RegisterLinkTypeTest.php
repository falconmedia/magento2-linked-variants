<?php

/**
 * @package     FalconMedia_LinkedVariants
 * @author      Henk Valk (henk@falconmedia.nl)
 *
 * Copyright (c) Falcon Media (www.falconmedia.nl)
 */

declare(strict_types=1);

namespace FalconMedia\LinkedVariants\Test\Unit\Plugin\Catalog;

use FalconMedia\LinkedVariants\Model\LinkType;
use FalconMedia\LinkedVariants\Plugin\Catalog\RegisterLinkType;
use Magento\Catalog\Model\Product\LinkTypeProvider;
use PHPUnit\Framework\TestCase;

class RegisterLinkTypeTest extends TestCase
{
    private function plugin(?int $id): RegisterLinkType
    {
        $linkType = $this->createMock(LinkType::class);
        $linkType->method('getId')->willReturn($id);

        return new RegisterLinkType($linkType);
    }

    public function testAddsVariantTypeWithDatabaseId(): void
    {
        $provider = $this->createMock(LinkTypeProvider::class);

        self::assertSame(
            ['related' => 1, 'variant' => 20],
            $this->plugin(20)->afterGetLinkTypes($provider, ['related' => 1])
        );
    }

    public function testLeavesTypesAloneBeforeThePatchHasRun(): void
    {
        $provider = $this->createMock(LinkTypeProvider::class);

        self::assertSame(['related' => 1], $this->plugin(null)->afterGetLinkTypes($provider, ['related' => 1]));
    }
}
