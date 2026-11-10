<?php

namespace Concrete\Package\EventManagerKit\Controller\SinglePage\Dashboard\EventManager;

use EventManagerKit\Entity\EventType;
use EventManagerKit\Dashboard\NamedEntityPage;
use Concrete\Package\EventManagerKit\Controller\Search\EventTypes as SearchController;

class EventTypes extends NamedEntityPage
{
    protected function entityClass(): string
    {
        return EventType::class;
    }

    protected function basePath(): string
    {
        return '/dashboard/event_manager/event_types';
    }

    protected function searchControllerClass(): string
    {
        return SearchController::class;
    }

    protected function label(): string
    {
        return t('Event Type');
    }

    protected function pluralLabel(): string
    {
        return t('Event Types');
    }
}
