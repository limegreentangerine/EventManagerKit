<?php

namespace EventManagerKit\Search\ItemList;

use EventManagerKit\Entity\Location;

class Locations extends AbstractList
{
    protected $autoSortColumns = [
        'n.name',
    ];

    protected function entityClass(): string
    {
        return Location::class;
    }

    protected function table(): string
    {
        return 'emLocations';
    }

    protected function alias(): string
    {
        return 'l';
    }

    public function createQuery()
    {
        parent::createQuery();
        $this->joinTranslations('emLocationNames', 'locationId');
    }
}
