<?php

namespace EventManagerKit\Search\Column\Set;

use Concrete\Core\Search\Column\{Column, Set};

class Events extends Set
{
    public function __construct()
    {
        $this->addColumn(new Column('n.name', t('Name'), 'getName', true));
        $this->addColumn(new Column('e.startDate', t('Start'), 'getStartString', true));
        $this->addColumn(new Column('e.endDate', t('End'), 'getEndString', true));
        $this->addColumn(new Column('e.location', t('Location'), 'getLocationName', false));
        $this->addColumn(new Column('e.type', t('Type'), 'getTypeName', false));

        $this->setDefaultSortColumn($this->getColumnByKey('e.startDate'), 'desc');
    }
}
