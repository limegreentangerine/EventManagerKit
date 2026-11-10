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

    public function testHeadHasAlternatesAndTwitterButNoDuplicateCanonicalOrOgCore(): void
    {
        $head = SeoTags::head(['en-GB' => 'https://x.test/a'], 'Tom "&" Jerry', '', 'https://x.test/i.jpg');

        $this->assertStringContainsString('hreflang="en-GB"', $head);
        $this->assertStringContainsString('name="twitter:title" content="Tom &quot;&amp;&quot; Jerry"', $head);
        $this->assertStringContainsString('name="twitter:card" content="summary_large_image"', $head);
        $this->assertStringContainsString('property="og:image"', $head);
        $this->assertStringNotContainsString('canonical', $head);
        $this->assertStringNotContainsString('og:title', $head);
        $this->assertStringNotContainsString('twitter:description', $head);
    }

    public function testAddToSitemapAppendsUrlsWithAlternates(): void
    {
        $doc = new \SimpleXMLElement('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"/>');
        SeoTags::addToSitemap($doc, [['loc' => 'https://x.test/a?b=1&c=2', 'alternates' => ['en-GB' => 'https://x.test/a'], 'lastmod' => new \DateTimeImmutable('2026-11-10 12:30:00', new \DateTimeZone('Europe/London'))]], 'weekly', '0.5');
        $xml = (string) $doc->asXML();

        $this->assertStringContainsString('<loc>https://x.test/a?b=1&amp;c=2</loc>', $xml);
        $this->assertStringContainsString('<lastmod>2026-11-10T12:30:00+00:00</lastmod><changefreq>weekly</changefreq>', preg_replace('/>\s+</', '><', $xml));
        $this->assertMatchesRegularExpression('#<xhtml:link[^>]*hreflang="en-GB"#', $xml);
        $this->assertNotFalse(simplexml_load_string($xml));
    }
}
