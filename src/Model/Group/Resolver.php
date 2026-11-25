<?php

/**
 * @package     FalconMedia_LinkedVariants
 * @author      Henk Valk (henk@falconmedia.nl)
 *
 * Copyright (c) Falcon Media (www.falconmedia.nl)
 */

declare(strict_types=1);

namespace FalconMedia\LinkedVariants\Model\Group;

/**
 * Computes the link sets that keep a variant group symmetric and transitive.
 *
 * Linking A to B and C makes B and C variants of each other and of A. Removing a
 * product from a group also removes the group from that product.
 */
class Resolver
{
    /**
     * Returns the desired linked ids per product id, only for products whose links change.
     *
     * @param int[] $newIds links saved on the product
     * @param int[] $oldIds links the product had before the save
     * @param callable(int): int[] $linkedIdsOf returns the current links of a product
     * @return array<int, int[]>
     */
    public function resolve(int $productId, array $newIds, array $oldIds, callable $linkedIdsOf): array
    {
        $newIds = $this->normalize($newIds, $productId);
        $oldIds = $this->normalize($oldIds, $productId);
        $removed = array_values(array_diff($oldIds, $newIds));

        $group = $this->collectGroup(array_merge([$productId], $newIds), $removed, $linkedIdsOf);

        $changes = [];
        foreach ($group as $memberId) {
            $desired = array_values(array_diff($group, [$memberId]));
            if ($memberId === $productId) {
                $desired = $this->orderLike($desired, $newIds);
            }
            $current = $memberId === $productId ? $newIds : $this->normalize($linkedIdsOf($memberId), $memberId);
            if (!$this->sameSet($desired, $current)) {
                $changes[$memberId] = $desired;
            }
        }

        foreach ($removed as $removedId) {
            $current = $this->normalize($linkedIdsOf($removedId), $removedId);
            $desired = array_values(array_diff($current, $group));
            if (!$this->sameSet($desired, $current)) {
                $changes[$removedId] = $desired;
            }
        }

        return $changes;
    }

    /**
     * Walks the link graph from the seed ids and returns every reachable id, skipping removed ids.
     *
     * @param int[] $seedIds
     * @param int[] $removed
     * @return int[]
     */
    private function collectGroup(array $seedIds, array $removed, callable $linkedIdsOf): array
    {
        $group = [];
        $queue = $seedIds;
        while ($queue !== []) {
            $id = (int) array_shift($queue);
            if (isset($group[$id]) || in_array($id, $removed, true)) {
                continue;
            }
            $group[$id] = $id;
            foreach ($linkedIdsOf($id) as $linkedId) {
                $queue[] = (int) $linkedId;
            }
        }

        return array_values($group);
    }

    /**
     * @param int[] $ids
     * @return int[]
     */
    private function normalize(array $ids, int $self): array
    {
        $result = [];
        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id > 0 && $id !== $self) {
                $result[$id] = $id;
            }
        }

        return array_values($result);
    }

    /**
     * Puts the ids present in $preferredOrder first, in that order.
     *
     * @param int[] $ids
     * @param int[] $preferredOrder
     * @return int[]
     */
    private function orderLike(array $ids, array $preferredOrder): array
    {
        $ordered = array_values(array_intersect($preferredOrder, $ids));

        return array_merge($ordered, array_values(array_diff($ids, $ordered)));
    }

    /**
     * @param int[] $a
     * @param int[] $b
     */
    private function sameSet(array $a, array $b): bool
    {
        sort($a);
        sort($b);

        return $a === $b;
    }
}
