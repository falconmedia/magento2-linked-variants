<?php

/**
 * @package     FalconMedia_LinkedVariants
 * @author      Henk Valk (henk@falconmedia.nl)
 *
 * Copyright (c) Falcon Media (www.falconmedia.nl)
 */

declare(strict_types=1);

namespace FalconMedia\LinkedVariants\Test\Unit\Setup\Patch\Data;

use FalconMedia\LinkedVariants\Model\ResourceModel\LinkTypeStorage;
use FalconMedia\LinkedVariants\Setup\Patch\Data\AddVariantLinkType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddVariantLinkTypeTest extends TestCase
{
    private LinkTypeStorage&MockObject $storage;

    private AddVariantLinkType $patch;

    protected function setUp(): void
    {
        $this->storage = $this->createMock(LinkTypeStorage::class);
        $this->patch = new AddVariantLinkType($this->storage);
    }

    /**
     * @param array<string, ?int> $existing link type ids by code
     */
    private function givenTypes(array $existing): void
    {
        $this->storage->method('findIdByCode')
            ->willReturnCallback(static fn (string $code): ?int => $existing[$code] ?? null);
    }

    public function testCreatesTypeWhenNeitherExists(): void
    {
        $this->givenTypes([]);
        $this->storage->expects($this->once())->method('insertType')->with('variant')->willReturn(9);
        $this->storage->expects($this->never())->method('renameType');
        $this->storage->expects($this->once())->method('insertAttribute')->with(9, 'position', 'int');

        $this->patch->apply();
    }

    public function testRenamesLegacyTypeInPlace(): void
    {
        $this->givenTypes(['color_variant' => 20]);
        $this->storage->expects($this->once())->method('renameType')->with(20, 'variant');
        $this->storage->expects($this->never())->method('insertType');
        $this->storage->expects($this->never())->method('deleteType');
        $this->storage->method('findAttributeId')->with(20, 'position')->willReturn(3);
        $this->storage->expects($this->never())->method('insertAttribute');

        $this->patch->apply();
    }

    public function testKeepsExistingVariantTypeWhenLegacyIsAbsent(): void
    {
        $this->givenTypes(['variant' => 20]);
        $this->storage->expects($this->never())->method('renameType');
        $this->storage->expects($this->never())->method('insertType');
        $this->storage->expects($this->never())->method('moveLinks');
        $this->storage->method('findAttributeId')->willReturn(3);
        $this->storage->expects($this->never())->method('insertAttribute');

        $this->patch->apply();
    }

    public function testMergesLegacyTypeIntoExistingVariantType(): void
    {
        $this->givenTypes(['variant' => 21, 'color_variant' => 20]);
        $this->storage->expects($this->once())->method('moveLinks')->with(20, 21);
        $this->storage->expects($this->once())->method('deleteType')->with(20);
        $this->storage->method('findAttributeId')->willReturn(3);

        $this->patch->apply();
    }

    public function testCreatesPositionAttributeBeforeMergingLinks(): void
    {
        $this->givenTypes(['variant' => 21, 'color_variant' => 20]);
        $calls = [];
        $this->storage->method('findAttributeId')->willReturn(null);
        $this->storage->expects($this->once())->method('insertAttribute')->with(21, 'position', 'int')
            ->willReturnCallback(static function () use (&$calls): void {
                $calls[] = 'insertAttribute';
            });
        $this->storage->expects($this->once())->method('moveLinks')->with(20, 21)
            ->willReturnCallback(static function () use (&$calls): void {
                $calls[] = 'moveLinks';
            });
        $this->storage->expects($this->once())->method('deleteType')->with(20)
            ->willReturnCallback(static function () use (&$calls): void {
                $calls[] = 'deleteType';
            });

        $this->patch->apply();

        $this->assertSame(['insertAttribute', 'moveLinks', 'deleteType'], $calls);
    }

    public function testIsIdempotent(): void
    {
        $this->givenTypes(['variant' => 20]);
        $this->storage->method('findAttributeId')->willReturn(3);
        $this->storage->expects($this->never())->method('insertAttribute');

        $this->patch->apply();
        $this->patch->apply();
    }
}
