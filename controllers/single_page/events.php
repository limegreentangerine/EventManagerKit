<?php

namespace Concrete\Package\EventManagerKit\Controller\SinglePage;

use Page;
use Concrete\Core\Permission\Checker;
use Concrete\Core\Support\Facade\Url;
use Doctrine\ORM\EntityManagerInterface;
use Concrete\Core\Package\PackageService;
use EventManagerKit\Translation\LocaleContext;
use Concrete\Core\Page\Controller\PageController;
use EventManagerKit\Frontend\{EventFinder, IcsEndpoint, LocationMap, SeoTags};

/**
 * Public event page: /events/{slug}. A single page, so it renders in the
 * site's theme. Slugs are per language; a slug from another language
 * redirects to the visitor's own.
 */
class Events extends PageController
{
    /**
     * Optional integration: with the `mapbox` package installed and a located
     * venue, load Mapbox GL and MapboxKit's <mapbox-component> and hand the
     * view its config. Otherwise the view falls back to an external map link.
     */
    private function addMap($event): void
    {
        $config = $event->getLocation() ? LocationMap::config($event->getLocation()) : null;
        $package = $config ? $this->app->make(PackageService::class)->getByHandle('mapbox') : null;
        if (!$package || !class_exists(\MapboxKit\Config\MapboxEnv::class)) {
            return;
        }

        $html = $this->app->make('helper/html');
        $version = \MapboxKit\Config\MapboxEnv::glVersionOrDefault();
        $base = $package->getRelativePath() . '/blocks/mapbox/';
        $this->addHeaderItem($html->css('//api.mapbox.com/mapbox-gl-js/v' . $version . '/mapbox-gl.css'));
        $this->addHeaderItem($html->css($base . 'view.css'));
        $this->addFooterItem($html->javascript('//api.mapbox.com/mapbox-gl-js/v' . $version . '/mapbox-gl.js'));
        $this->addFooterItem($html->javascript($base . 'view.js'));
        $this->set('mapboxConfig', $config);
    }
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
        if ($event->isPublished()) {
            $url = fn(string $l) => IcsEndpoint::eventUrl($event, $l);
            $file = $event->getImageID() ? \Concrete\Core\File\File::getByID($event->getImageID()) : null;
            $this->addHeaderItem(SeoTags::head(
                (string) $url($locale),
                SeoTags::alternates($event, $url),
                $event->getName($locale),
                (string) $this->get('pageDescription'),
                $file ? $file->getURL() : null,
            ));
        }
        $this->addMap($event);
        $this->set('event', $event);
        $this->set('locale', $locale);
        $this->set('isDraft', !$event->isPublished());
    }
}
