<?php

namespace EventManagerKit\Search\ItemList;

use EventManagerKit\Entity\AgeGroup;

class AgeGroups extends AbstractList
{
    protected $autoSortColumns = [
        'n.name',
        't.sortOrder',
    ];

    protected function entityClass(): string
    {
        return AgeGroup::class;
    }

    protected function table(): string
    {
        return 'emAgeGroups';
    }

    protected function alias(): string
    {
        return 't';
    }

    public function createQuery()
    {
        parent::createQuery();
        $this->joinTranslations('emAgeGroupNames', 'ageGroupId');
    }
}
