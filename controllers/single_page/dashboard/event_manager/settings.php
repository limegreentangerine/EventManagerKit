<?php

namespace Concrete\Package\EventManagerKit\Controller\SinglePage\Dashboard\EventManager;

use EventManagerKit\Activities;
use Concrete\Core\Package\PackageService;
use Concrete\Core\Page\Controller\DashboardPageController;

class Settings extends DashboardPageController
{
    protected $helpers = ['form'];

    public function view()
    {
        $this->set('activitiesEnabled', Activities::enabled());
    }

    public function save()
    {
        if (!$this->token->validate('em-settings')) {
            $this->error->add($this->token->getErrorMessage());

            return $this->view();
        }

        Activities::setEnabled($this->request->request->get('activities') === '1');
        // Adds or removes the Age Groups dashboard page; the data is kept either way.
        $pkg = $this->app->make(PackageService::class)->getByHandle('event_manager_kit');
        $pkg->getController()->syncActivityPages($pkg);

        $this->flash('success', t('Settings saved.'));

        return $this->buildRedirect('/dashboard/event_manager/settings')->send();
    }
}
