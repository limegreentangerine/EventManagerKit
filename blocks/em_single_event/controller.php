<?php

namespace Concrete\Package\EventManagerKit\Block\EmSingleEvent;

defined('C5_EXECUTE') or die('Access Denied.');

use EventManagerKit\Entity\Event;
use Concrete\Core\Support\Facade\Url;
use Concrete\Core\Block\BlockController;
use Doctrine\ORM\EntityManagerInterface;
use Concrete\Core\Error\ErrorList\ErrorList;
use EventManagerKit\Translation\LocaleContext;
use EventManagerKit\Frontend\{EventQuery, EventsEndpoint};

class Controller extends BlockController
{
    protected $btTable = 'btEmSingleEvent';
    protected $btDefaultSet = 'event_manager_kit';
    protected $btInterfaceWidth = 500;
    protected $btInterfaceHeight = 300;
    // Events change in the dashboard at any time; never serve stale output.
    protected $btCacheBlockOutput = false;

    /**
     * Options for the event select, as id => name (soonest first).
     *
     * @return array<string,string>
     */
    private function eventOptions(): array
    {
        $locale = LocaleContext::active();
        $events = app(EntityManagerInterface::class)->getRepository(Event::class)->findBy([], ['startDate' => 'DESC']);
        $options = ['' => t('Choose an event…')];
        foreach ($events as $event) {
            $options[$event->getID()] = $event->getName($locale) . ($event->isPublished() ? '' : ' (' . t('Draft') . ')');
        }

        return $options;
    }

    public function getBlockTypeName()
    {
        return t('Single Event');
    }

    public function getBlockTypeDescription()
    {
        return t('Shows one chosen event.');
    }

    public function add()
    {
        $this->set('title', '');
        $this->set('eventID', '');
        $this->set('events', $this->eventOptions());
    }

    public function edit()
    {
        $this->set('events', $this->eventOptions());
    }

    public function view()
    {
        // Shell only; the event is fetched client-side so cached pages never go stale.
        $this->requireAsset('event_manager_kit/events');
        $this->set('endpoint', (string) Url::to(EventsEndpoint::PATH));
        $this->set('locale', LocaleContext::active());
    }

    public function validate($args)
    {
        $errors = new ErrorList();
        $id = $args['eventID'] ?? '';
        if (!is_string($id) || !preg_match(EventQuery::GUID, $id)) {
            $errors->add(t('Choose an event.'));
        }

        return $errors;
    }

    public function save($args)
    {
        $args['title'] = trim((string) ($args['title'] ?? ''));
        $args['eventID'] = strtolower((string) ($args['eventID'] ?? ''));

        parent::save($args);
    }
}
