<?php

/**
 * @package     FalconMedia_LinkedVariants
 * @author      Henk Valk (henk@falconmedia.nl)
 *
 * Copyright (c) Falcon Media (www.falconmedia.nl)
 */

declare(strict_types=1);

namespace FalconMedia\LinkedVariants\Test\Unit\Model\Group;

use FalconMedia\LinkedVariants\Model\Group\Resolver;
use PHPUnit\Framework\TestCase;

class ResolverTest extends TestCase
{
    private Resolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new Resolver();
    }

    /**
     * @param array<int, int[]> $links
     * @return callable(int): int[]
     */
    private function graph(array $links): callable
    {
        return static fn (int $id): array => $links[$id] ?? [];
    }

    public function testLinkingTwoProductsLinksThemToEachOther(): void
    {
        $links = [1 => [2, 3]];
        $changes = $this->resolver->resolve(1, [2, 3], [], $this->graph($links));

        self::assertSame([2 => [1, 3], 3 => [1, 2]], $this->sorted($changes));
    }

    public function testJoiningAnExistingGroupMergesBothGroups(): void
    {
        $links = [1 => [2], 2 => [1], 3 => [4], 4 => [3]];
        $changes = $this->resolver->resolve(1, [2, 3], [2], $this->graph($links));

        self::assertSame(
            [1 => [2, 3, 4], 2 => [1, 3, 4], 3 => [1, 2, 4], 4 => [1, 2, 3]],
            $this->sorted($changes)
        );
    }

    public function testRemovingAProductDetachesItFromTheGroup(): void
    {
        $links = [1 => [3], 2 => [1, 3], 3 => [1, 2]];
        $changes = $this->resolver->resolve(1, [3], [2, 3], $this->graph($links));

        self::assertSame([2 => [], 3 => [1]], $this->sorted($changes));
    }

    public function testNoChangeWhenGroupAlreadyConsistent(): void
    {
        $links = [1 => [2, 3], 2 => [1, 3], 3 => [1, 2]];
        $changes = $this->resolver->resolve(1, [3, 2], [2, 3], $this->graph($links));

        self::assertSame([], $changes);
    }

    public function testCurrentProductKeepsSavedOrder(): void
    {
        $links = [1 => [3, 2], 3 => [4], 4 => [3]];
        $changes = $this->resolver->resolve(1, [3, 2], [], $this->graph($links));

        self::assertSame([3, 2, 4], $changes[1]);
    }

    public function testSelfLinksAreIgnored(): void
    {
        $links = [1 => [1, 2]];
        $changes = $this->resolver->resolve(1, [1, 2], [], $this->graph($links));

        self::assertSame([2 => [1]], $this->sorted($changes));
    }

    /**
     * @param array<int, int[]> $changes
     * @return array<int, int[]>
     */
    private function sorted(array $changes): array
    {
        ksort($changes);
        foreach ($changes as &$ids) {
            sort($ids);
        }

        return $changes;
    }
}
