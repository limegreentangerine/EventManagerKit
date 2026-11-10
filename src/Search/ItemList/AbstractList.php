<?php

namespace EventManagerKit\Search\ItemList;

use ClassKit\Search\ItemList\ListTrait;
use Pagerfanta\Adapter\DoctrineDbalAdapter;
use EventManagerKit\Translation\LocaleContext;
use Concrete\Core\Search\Pagination\Pagination;
use Concrete\Core\Application\ApplicationAwareTrait;
use Concrete\Core\Search\ItemList\Database\ItemList;
use Concrete\Core\Application\ApplicationAwareInterface;

/**
 * Shared DBAL search list: selects ids, then resolves entities through the
 * identity map (see PrefetchesEntities). Subclasses name the entity, table and
 * alias, and add their own joins/filters in createQuery().
 */
abstract class AbstractList extends ItemList implements ApplicationAwareInterface
{
    use ApplicationAwareTrait;
    use ListTrait;
    use PrefetchesEntities;

    /** @return class-string */
    abstract protected function entityClass(): string;

    abstract protected function table(): string;

    abstract protected function alias(): string;

    protected function createPaginationObject()
    {
        $alias = $this->alias();
        $adapter = new DoctrineDbalAdapter(
            $this->deliverQueryObject(),
            function (\Doctrine\DBAL\Query\QueryBuilder $query) use ($alias) {
                $query
                    ->resetQueryParts(['groupBy', 'orderBy'])
                    ->select("count(distinct {$alias}.id)")
                    ->setMaxResults(1);
            },
        );

        return new Pagination($this, $adapter);
    }

    public function createQuery()
    {
        $this->query->select($this->alias() . '.id')->from($this->table(), $this->alias());
    }

    /**
     * Left-join a per-locale rows table (names/contents) for the locale being
     * displayed, so the list can sort and filter on the visible name.
     */
    protected function joinTranslations(string $table, string $foreignKey, string $joinAlias = 'n'): void
    {
        $alias = $this->alias();
        $this->query->leftJoin(
            $alias,
            $table,
            $joinAlias,
            "{$joinAlias}.{$foreignKey} = {$alias}.id AND {$joinAlias}.locale = " . $this->query->createNamedParameter(LocaleContext::active()),
        );
    }

    public function filterByName($name)
    {
        $name = (string) $name;
        if ($name !== '') {
            $this->filterBy('n.name', $name, 'like');
        }
    }

    public function getTotalResults()
    {
        $query = $this->deliverQueryObject();
        $query
            ->resetQueryParts(['groupBy', 'orderBy'])
            ->select('count(distinct ' . $this->alias() . '.id)')
            ->setMaxResults(1);

        return (int) $query->execute()->fetchOne();
    }

    public function executeGetResults()
    {
        return $this->prefetchEntities($this->entityClass(), parent::executeGetResults());
    }

    public function getResult($queryRow)
    {
        return $this->findEntity($this->entityClass(), $queryRow['id']);
    }
}
