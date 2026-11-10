<?php

namespace Concrete\Package\EventManagerKit\Controller\Search;

use EventManagerKit\Search\AbstractSearchController;
use EventManagerKit\Search\{Column\Set\EventTypes as ColumnSet, ItemList\EventTypes as SearchList, Result\EventTypes as SearchResult};

class EventTypes extends AbstractSearchController
{
    protected function stickyKey(): string
    {
        return 'em.event_types';
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
        return 'dashboard/event_manager/event_types/';
    }
}
