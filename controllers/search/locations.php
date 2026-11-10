<?php

namespace Concrete\Package\EventManagerKit\Controller\Search;

use EventManagerKit\Search\AbstractSearchController;
use EventManagerKit\Search\{Column\Set\Locations as ColumnSet, ItemList\Locations as SearchList, Result\Locations as SearchResult};

class Locations extends AbstractSearchController
{
    protected function stickyKey(): string
    {
        return 'em.locations';
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
        return 'dashboard/event_manager/locations/';
    }
}
