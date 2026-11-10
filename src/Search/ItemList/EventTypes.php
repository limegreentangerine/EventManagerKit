<?php

namespace EventManagerKit\Search\ItemList;

use EventManagerKit\Entity\EventType;

class EventTypes extends AbstractList
{
    protected $autoSortColumns = [
        'n.name',
    ];

    protected function entityClass(): string
    {
        return EventType::class;
    }

    protected function table(): string
    {
        return 'emEventTypes';
    }

    protected function alias(): string
    {
        return 't';
    }

    public function createQuery()
    {
        parent::createQuery();
        $this->joinTranslations('emEventTypeNames', 'eventTypeId');
    }
}
