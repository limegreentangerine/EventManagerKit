<?php

namespace Concrete\Package\EventManagerKit\Controller\Search;

use EventManagerKit\Search\AbstractSearchController;
use EventManagerKit\Search\{Column\Set\Events as ColumnSet, ItemList\Events as SearchList, Result\Events as SearchResult};

class Events extends AbstractSearchController
{
    protected function stickyKey(): string
    {
        return 'em.events';
    }

    protected function listClass(): string
    {
        return SearchList::class;
    }

    protected function columnSetClass(): string
    {
        return ColumnSet::class;
    }

    protected function resultClass(): string
    {
        return SearchResult::class;
    }

    protected function listUrl(): string
    {
        return 'dashboard/event_manager/events/';
    }

    protected function applyFilters($searchList, array $req): void
    {
        foreach ([
            'type' => 'filterByType',
            'location' => 'filterByLocation',
        ] as $key => $method) {
            if (isset($req[$key]) && is_string($req[$key]) && $req[$key] !== '') {
                $searchList->$method($req[$key]);
            }
        }
    }
}
