<?php

namespace Concrete\Package\EventManagerKit\Block\EventCalendar;

defined('C5_EXECUTE') or die('Access Denied.');

use EventManagerKit\Activities;
use Concrete\Core\Support\Facade\Url;
use Concrete\Core\Block\BlockController;
use EventManagerKit\Translation\LocaleContext;
use EventManagerKit\Frontend\{EventQuery, EventsEndpoint};

class Controller extends BlockController
{
    protected $btTable = 'btEmEventCalendar';
    protected $btDefaultSet = 'event_manager_kit';
    protected $btInterfaceWidth = 500;
    protected $btInterfaceHeight = 300;
    // The block renders a static shell; events are fetched from the endpoint.
    protected $btCacheBlockOutput = false;

    public function getBlockTypeName()
    {
        return t('Event Calendar');
    }

    public function getBlockTypeDescription()
    {
        return t('Month view of published events with optional filters.');
    }

    public function add()
    {
        $this->set('activitiesEnabled', Activities::enabled());
        $this->set('kind', '');
        $this->set('title', '');
        $this->set('showFilters', true);
    }

    public function view()
    {
        // Shell only; events are fetched client-side so cached pages never go stale.
        $this->requireAsset('event_manager_kit/events');
        $this->set('endpoint', (string) Url::to(EventsEndpoint::PATH));
        $this->set('locale', LocaleContext::active());
        // Ignore the setting while activities are switched off.
        $this->set('kind', Activities::enabled() ? (string) $this->kind : '');
    }

    public function edit()
    {
        $this->set('activitiesEnabled', Activities::enabled());
    }

    public function save($args)
    {
        $args['title'] = trim((string) ($args['title'] ?? ''));
        $args['kind'] = in_array($args['kind'] ?? '', EventQuery::KINDS, true) ? $args['kind'] : '';
        $args['showFilters'] = empty($args['showFilters']) ? 0 : 1;

        parent::save($args);
    }
}
