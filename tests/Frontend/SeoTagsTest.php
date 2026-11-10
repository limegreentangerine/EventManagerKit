<?php

declare(strict_types=1);

namespace EventManagerKit\Tests\Frontend;

use PHPUnit\Framework\TestCase;
use EventManagerKit\Entity\Event;
use EventManagerKit\Frontend\SeoTags;

final class SeoTagsTest extends TestCase
{
    public function testAlternatesOnlyForLanguagesWithASlug(): void
    {
        $event = (new Event())->setContent('en_GB', 'Open day', null, 'open-day')->setContent('cy_GB', 'Diwrnod agored', null, 'diwrnod-agored')->setContent('fr_FR', 'Journée', null, null);
        $alternates = SeoTags::alternates($event, fn(string $l) => 'https://x.test/' . $l);

        $this->assertSame(['cy-GB' => 'https://x.test/cy_GB', 'en-GB' => 'https://x.test/en_GB'], $alternates);
    }

    public function testHeadEscapesAndIncludesOpenGraph(): void
    {
        $head = SeoTags::head('https://x.test/a', ['en-GB' => 'https://x.test/a'], 'Tom "&" Jerry', '', 'https://x.test/i.jpg');

        $this->assertStringContainsString('<link rel="canonical" href="https://x.test/a">', $head);
        $this->assertStringContainsString('hreflang="en-GB"', $head);
        $this->assertStringContainsString('property="og:title" content="Tom &quot;&amp;&quot; Jerry"', $head);
        $this->assertStringContainsString('name="twitter:card" content="summary_large_image"', $head);
        $this->assertStringNotContainsString('og:description', $head);
    }

    public function testAddToSitemapAppendsUrlsWithAlternates(): void
    {
        $doc = new \SimpleXMLElement('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"/>');
        SeoTags::addToSitemap($doc, [['loc' => 'https://x.test/a?b=1&c=2', 'alternates' => ['en-GB' => 'https://x.test/a']]], 'weekly', '0.5');
        $xml = (string) $doc->asXML();

        $this->assertStringContainsString('<loc>https://x.test/a?b=1&amp;c=2</loc>', $xml);
        $this->assertStringContainsString('<changefreq>weekly</changefreq>', $xml);
        $this->assertMatchesRegularExpression('#<xhtml:link[^>]*hreflang="en-GB"#', $xml);
        $this->assertNotFalse(simplexml_load_string($xml));
    }
}
