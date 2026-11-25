<?php

/**
 * @package     FalconMedia_LinkedVariants
 * @author      Henk Valk (henk@falconmedia.nl)
 *
 * Copyright (c) Falcon Media (www.falconmedia.nl)
 */

declare(strict_types=1);

namespace FalconMedia\LinkedVariants\Test\Unit\Model;

use FalconMedia\LinkedVariants\Model\ResourceModel\VariantLink;
use FalconMedia\LinkedVariants\Model\VariantLinks;
use PHPUnit\Framework\TestCase;

class VariantLinksTest extends TestCase
{
    public function testDelegatesToTheResourceModel(): void
    {
        $resource = $this->createMock(VariantLink::class);
        $resource->method('getLinkedIdsByProduct')->with([1, 2])->willReturn([1 => [5, 6]]);
        $resource->method('getLinkedIds')->with(1)->willReturn([5, 6]);

        $links = new VariantLinks($resource);

        self::assertSame([1 => [5, 6]], $links->getVariantLinks([1, 2]));
        self::assertSame([5, 6], $links->getLinkedProductIds(1));
    }
}
