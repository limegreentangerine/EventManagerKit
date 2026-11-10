<?php

declare(strict_types=1);

namespace EventManagerKit\Frontend;

use Doctrine\ORM\EntityManagerInterface;
use Concrete\Core\Page\Sitemap\Event\XmlReadyEvent;

/**
 * Adds published events (once per language, with hreflang alternates) to
 * Concrete's own sitemap.xml, via the on_sitemap_xml_ready event.
 */
final class SitemapListener
{
    public static function addEvents(XmlReadyEvent $event): void
    {
        $config = app('config');
        $finder = new EventFinder(app(EntityManagerInterface::class));
        $entries = [];
        foreach ($finder->allPublished() as $item) {
            $alternates = SeoTags::alternates($item, fn(string $locale) => IcsEndpoint::eventUrl($item, $locale));
            foreach ($alternates as $url) {
                $entries[] = ['loc' => $url, 'alternates' => $alternates, 'lastmod' => $item->getDateUpdated()];
            }
        }
        SeoTags::addToSitemap(
            $event->getDocument(),
            $entries,
            (string) $config->get('concrete.sitemap_xml.frequency'),
            (string) $config->get('concrete.sitemap_xml.priority'),
        );
    }
}
