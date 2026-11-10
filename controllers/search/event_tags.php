<?php

namespace Concrete\Package\EventManagerKit\Controller\Search;

use EventManagerKit\Search\AbstractSearchController;
use EventManagerKit\Search\{Column\Set\EventTags as ColumnSet, ItemList\EventTags as SearchList, Result\EventTags as SearchResult};

class EventTags extends AbstractSearchController
{
    protected function stickyKey(): string
    {
        return 'em.event_tags';
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
        return 'dashboard/event_manager/event_tags/';
    }
}
