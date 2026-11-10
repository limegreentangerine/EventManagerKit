<?php

namespace EventManagerKit\Search\ItemList;

use EventManagerKit\Entity\EventTag;

class EventTags extends AbstractList
{
    protected $autoSortColumns = [
        'n.name',
    ];

    protected function entityClass(): string
    {
        return EventTag::class;
    }

    protected function table(): string
    {
        return 'emEventTags';
    }

    protected function alias(): string
    {
        return 't';
    }

    public function createQuery()
    {
        parent::createQuery();
        $this->joinTranslations('emEventTagNames', 'eventTagId');
    }
}
