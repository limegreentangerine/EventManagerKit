<?php

namespace Concrete\Package\EventManagerKit\Block\EventCalendar;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Url;
use Concrete\Core\Block\BlockController;
use EventManagerKit\Frontend\EventsEndpoint;
use EventManagerKit\Translation\LocaleContext;

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
        $this->set('title', '');
        $this->set('showFilters', true);
    }

    public function view()
    {
        // Shell only; events are fetched client-side so cached pages never go stale.
        $this->requireAsset('event_manager_kit/events');
        $this->set('endpoint', (string) Url::to(EventsEndpoint::PATH));
        $this->set('locale', LocaleContext::active());
    }

    public function save($args)
    {
        $args['title'] = trim((string) ($args['title'] ?? ''));
        $args['showFilters'] = empty($args['showFilters']) ? 0 : 1;

        parent::save($args);
    }
}
