<?php

namespace Concrete\Package\EventManagerKit\Controller\SinglePage\Dashboard;

use Concrete\Core\Page\Controller\DashboardPageController;

class EventManager extends DashboardPageController
{
    public function view()
    {
        return $this->buildRedirect('/dashboard/event_manager/events')->send();
    }
}
