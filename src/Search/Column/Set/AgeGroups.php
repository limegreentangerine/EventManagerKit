<?php

namespace EventManagerKit\Search\Column\Set;

use Concrete\Core\Search\Column\{Column, Set};

class AgeGroups extends Set
{
    public function __construct()
    {
        $this->addColumn(new Column('n.name', t('Name'), 'getName', true));
        $this->addColumn(new Column('t.sortOrder', t('Order'), 'getSortOrder', true));
        $this->addColumn(new Column('t.handle', t('Handle'), 'getHandle', false));
        $this->setDefaultSortColumn($this->getColumnByKey('t.sortOrder'), 'asc');
    }
}
