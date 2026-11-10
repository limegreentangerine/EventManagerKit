<?php

declare(strict_types=1);

namespace EventManagerKit\Frontend;

use EventManagerKit\Entity\Event;

/**
 * Head tags and sitemap XML for public event pages. Pure string building.
 */
final class SeoTags
{
    /**
     * One URL per language the event has a slug for, keyed by hreflang (en_GB becomes en-GB).
     *
     * @param  callable(string):?string $urlFor locale => absolute URL (null when no slug)
     * @return array<string,string>
     */
    public static function alternates(Event $event, callable $urlFor): array
    {
        $out = [];
        foreach ($event->getContents() as $content) {
            $url = $content->getSlug() ? $urlFor($content->getLocale()) : null;
            if ($url) {
                $out[str_replace('_', '-', $content->getLocale())] = $url;
            }
        }
        ksort($out);

        return $out;
    }

    /**
     * Head tags Concrete does not emit itself: hreflang alternates, Twitter
     * card and og:image. (Concrete adds the canonical link and og:title,
     * og:description, og:type and og:url from the page object.)
     *
     * @param array<string,string> $alternates hreflang => URL
     */
    public static function head(array $alternates, string $title, string $description, ?string $image): string
    {
        $e = fn(string $v) => htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $tags = [];
        foreach ($alternates as $lang => $url) {
            $tags[] = '<link rel="alternate" hreflang="' . $e($lang) . '" href="' . $e($url) . '">';
        }
        $meta = ['twitter:card' => $image ? 'summary_large_image' : 'summary', 'twitter:title' => $title];
        if ($description !== '') {
            $meta['twitter:description'] = $description;
        }
        if ($image) {
            $meta += ['og:image' => $image, 'twitter:image' => $image];
        }
        foreach ($meta as $name => $value) {
            // Open Graph uses property=, Twitter uses name=.
            $tags[] = '<meta ' . (str_starts_with($name, 'og:') ? 'property' : 'name') . '="' . $e($name) . '" content="' . $e($value) . '">';
        }

        return implode("\n", $tags) . "\n";
    }

    /**
     * Adds one <url> per entry (with xhtml:link hreflang alternates) to a
     * sitemap document.
     *
     * @param list<array{loc:string,alternates:array<string,string>}> $entries
     */
    public static function addToSitemap(\SimpleXMLElement $document, array $entries, string $changeFrequency, string $priority): void
    {
        $xhtml = 'http://www.w3.org/1999/xhtml';
        foreach ($entries as $entry) {
            $url = $document->addChild('url');
            $url->addChild('loc', htmlspecialchars($entry['loc'], ENT_XML1));
            $url->addChild('changefreq', $changeFrequency);
            $url->addChild('priority', $priority);
            foreach ($entry['alternates'] as $lang => $href) {
                $link = $url->addChild('xhtml:link', null, $xhtml);
                $link->addAttribute('rel', 'alternate');
                $link->addAttribute('hreflang', $lang);
                $link->addAttribute('href', $href);
            }
        }
    }
}
