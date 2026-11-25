<?php

namespace Concrete\Package\EventManagerKit\Controller\Search;

use EventManagerKit\Search\AbstractSearchController;
use EventManagerKit\Search\{Column\Set\AgeGroups as ColumnSet, ItemList\AgeGroups as SearchList, Result\AgeGroups as SearchResult};

class AgeGroups extends AbstractSearchController
{
    protected function stickyKey(): string
    {
        return 'em.age_groups';
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
        return 'dashboard/event_manager/age_groups/';
    }
}
