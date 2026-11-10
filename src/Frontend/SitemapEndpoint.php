<?php

declare(strict_types=1);

namespace EventManagerKit\Frontend;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * GET /events-sitemap.xml: every published event, once per language, with
 * hreflang alternates. Concrete's own sitemap has no hook for extra URLs, so
 * add this file to robots.txt (Sitemap: ...) or to Search Console.
 */
final class SitemapEndpoint
{
    public const PATH = '/events-sitemap.xml';

    public function sitemap(): Response
    {
        $events = (new EventFinder(app(EntityManagerInterface::class)))->allPublished();
        $entries = [];
        foreach ($events as $event) {
            $alternates = SeoTags::alternates($event, fn(string $locale) => IcsEndpoint::eventUrl($event, $locale));
            foreach ($alternates as $url) {
                $entries[] = ['loc' => $url, 'alternates' => $alternates];
            }
        }

        return new Response(SeoTags::sitemap($entries), 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }
}
