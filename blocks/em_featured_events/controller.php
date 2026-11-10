<?php

namespace Concrete\Package\EventManagerKit\Block\EmFeaturedEvents;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Url;
use Concrete\Core\Block\BlockController;
use Concrete\Core\Error\ErrorList\ErrorList;
use EventManagerKit\Frontend\EventsEndpoint;
use EventManagerKit\Translation\LocaleContext;

class Controller extends BlockController
{
    protected $btTable = 'btEmFeaturedEvents';
    protected $btDefaultSet = 'event_manager_kit';
    protected $btInterfaceWidth = 500;
    protected $btInterfaceHeight = 300;
    // Events change in the dashboard at any time; never serve stale output.
    protected $btCacheBlockOutput = false;

    private function clampCount(mixed $count): int
    {
        return max(1, min(50, (int) $count ?: 3));
    }

    public function getBlockTypeName()
    {
        return t('Featured Events');
    }

    public function getBlockTypeDescription()
    {
        return t('Lists upcoming events marked as featured.');
    }

    public function add()
    {
        $this->set('title', '');
        $this->set('numEvents', 3);
    }

    public function view()
    {
        // Shell only; events are fetched client-side so cached pages never go stale.
        $this->requireAsset('event_manager_kit/events');
        $this->set('endpoint', (string) Url::to(EventsEndpoint::PATH));
        $this->set('locale', LocaleContext::active());
    }

    public function validate($args)
    {
        $errors = new ErrorList();
        $count = $args['numEvents'] ?? '';
        if (!is_numeric($count) || (int) $count < 1 || (int) $count > 50) {
            $errors->add(t('Number of events must be between 1 and 50.'));
        }

        return $errors;
    }

    public function save($args)
    {
        $args['title'] = trim((string) ($args['title'] ?? ''));
        $args['numEvents'] = $this->clampCount($args['numEvents'] ?? 3);

        parent::save($args);
    }
}
