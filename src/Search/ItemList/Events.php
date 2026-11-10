<?php

namespace EventManagerKit\Search\ItemList;

use EventManagerKit\Entity\Event;

class Events extends AbstractList
{
    protected $autoSortColumns = [
        'n.name',
        'e.startDate',
        'e.endDate',
    ];

    protected function entityClass(): string
    {
        return Event::class;
    }

    protected function table(): string
    {
        return 'emEvents';
    }

    protected function alias(): string
    {
        return 'e';
    }

    public function createQuery()
    {
        parent::createQuery();
        $this->joinTranslations('emEventContents', 'eventId');
    }

    public function filterByType($typeId)
    {
        if ((string) $typeId !== '') {
            $this->filterBy('e.eventTypeId', (string) $typeId);
        }
    }

    public function filterByLocation($locationId)
    {
        if ((string) $locationId !== '') {
            $this->filterBy('e.locationId', (string) $locationId);
        }
    }

    /**
     * Only events that have not finished yet.
     */
    public function filterByUpcoming(): void
    {
        $this->filterBy('e.endDate', gmdate('Y-m-d H:i:s'), '>=');
    }
}
