<?php

namespace Concrete\Package\EventManagerKit\Controller\SinglePage;

use Page;
use Concrete\Core\Support\Facade\Url;
use EventManagerKit\Frontend\EventFinder;
use Doctrine\ORM\EntityManagerInterface;
use Concrete\Core\Permission\Checker;
use EventManagerKit\Translation\LocaleContext;
use Concrete\Core\Page\Controller\PageController;

/**
 * Public event page: /events/{slug}. A single page, so it renders in the
 * site's theme. Slugs are per language; a slug from another language
 * redirects to the visitor's own.
 */
class Events extends PageController
{
    public function view($slug = null)
    {
        $slug = is_string($slug) ? $slug : '';
        $finder = new EventFinder($this->app->make(EntityManagerInterface::class));

        // Admins may preview drafts; everyone else gets "not found" for them.
        $dashboard = Page::getByPath('/dashboard/event_manager/events');
        $isAdmin = $dashboard && !$dashboard->isError() && (new Checker($dashboard))->canViewPage();

        $content = $slug !== '' ? $finder->findBySlug($slug, $isAdmin) : null;
        if (!$content) {
            return $this->replace('/page_not_found');
        }

        $event = $content->getEvent();
        $locale = LocaleContext::active();

        $ownSlug = $event->getSlug($locale);
        if ($ownSlug && $ownSlug !== $slug) {
            return $this->buildRedirect((string) Url::to('/events', $ownSlug), 301)->send();
        }

        $text = $event->getContent($locale);
        $this->set('pageTitle', $event->getName($locale));
        $this->set('pageDescription', mb_substr(trim(strip_tags((string) $text?->getDescription())), 0, 160));
        $this->set('event', $event);
        $this->set('locale', $locale);
        $this->set('isDraft', !$event->isPublished());
    }
}
