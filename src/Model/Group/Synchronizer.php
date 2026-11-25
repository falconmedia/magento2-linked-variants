<?php

/**
 * @package     FalconMedia_LinkedVariants
 * @author      Henk Valk (henk@falconmedia.nl)
 *
 * Copyright (c) Falcon Media (www.falconmedia.nl)
 */

declare(strict_types=1);

namespace FalconMedia\LinkedVariants\Model\Group;

use FalconMedia\LinkedVariants\Model\ResourceModel\VariantLink;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Indexer\CacheContext;
use Throwable;

/**
 * Applies the group changes computed by the resolver and invalidates affected product caches.
 */
class Synchronizer
{
    public function __construct(
        private readonly Resolver $resolver,
        private readonly VariantLink $variantLink,
        private readonly CacheContext $cacheContext,
        private readonly EventManager $eventManager
    ) {
    }

    /**
     * Synchronises the group of a product after its own links were saved.
     *
     * @param int[] $previousIds
     */
    public function sync(int $productId, array $previousIds): void
    {
        $newIds = $this->variantLink->getLinkedIds($productId);
        if ($newIds === [] && $previousIds === []) {
            return;
        }

        $cache = [$productId => $newIds];
        $linkedIdsOf = function (int $id) use (&$cache): array {
            if (!isset($cache[$id])) {
                $cache[$id] = $this->variantLink->getLinkedIds($id);
            }

            return $cache[$id];
        };

        $changes = $this->resolver->resolve($productId, $newIds, $previousIds, $linkedIdsOf);
        if ($changes === []) {
            return;
        }

        // One transaction for the whole group, so a failure never leaves it half linked.
        $connection = $this->variantLink->getConnection();
        $connection->beginTransaction();
        try {
            foreach ($changes as $memberId => $linkedIds) {
                $this->variantLink->saveLinkedIds((int) $memberId, $linkedIds);
            }
            $connection->commit();
        } catch (Throwable $exception) {
            $connection->rollBack();
            throw $exception;
        }

        $this->cacheContext->registerEntities(Product::CACHE_TAG, array_keys($changes));
        $this->eventManager->dispatch('clean_cache_by_tags', ['object' => $this->cacheContext]);
    }
}
