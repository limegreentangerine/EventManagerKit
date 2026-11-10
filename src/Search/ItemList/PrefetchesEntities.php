<?php

namespace EventManagerKit\Search\ItemList;

use Doctrine\ORM\EntityManagerInterface;

trait PrefetchesEntities
{
    /**
     * Load every row's entity in one query so the per-row findEntity() calls
     * in getResult() are answered from Doctrine's identity map rather than
     * costing a query each.
     */
    protected function prefetchEntities(string $entityClass, array $rows): array
    {
        $ids = array_map('strval', array_column($rows, 'id'));

        if ($ids) {
            app(EntityManagerInterface::class)->createQueryBuilder()
                ->select('e')
                ->from($entityClass, 'e')
                ->where('e.id IN (:ids)')
                ->setParameter('ids', $ids)
                ->getQuery()
                ->getResult();
        }

        return $rows;
    }

    /**
     * Resolve a row's entity via find(), which consults the identity map.
     * (The entities' static getByID() uses findOneBy(), which always runs a
     * query, so it would defeat prefetchEntities().)
     */
    protected function findEntity(string $entityClass, $id)
    {
        return app(EntityManagerInterface::class)->find($entityClass, (string) $id) ?: false;
    }
}
