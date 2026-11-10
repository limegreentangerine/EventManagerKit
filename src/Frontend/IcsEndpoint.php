<?php

declare(strict_types=1);

namespace EventManagerKit\Frontend;

use EventManagerKit\Entity\Event;
use Concrete\Core\Support\Facade\Url;
use EventManagerKit\Time\SiteTimezone;
use Doctrine\ORM\EntityManagerInterface;
use EventManagerKit\Calendar\IcsBuilder;
use EventManagerKit\Translation\LocaleContext;
use Symfony\Component\HttpFoundation\Response;

/**
 * Public, read-only iCalendar downloads: one event, or the upcoming feed.
 * Published events only (a draft's slug is "not found" here, as on its page).
 */
final class IcsEndpoint
{
    private const FEED_LIMIT = 500;
    public const EVENT_PATH = '/ajax/events-manager/ics/{slug}';
    public const FEED_PATH = '/ajax/events-manager/feed.ics';

    private function respond(array $events, string $name, string $filename): Response
    {
        $locale = LocaleContext::active();
        $host = (string) parse_url((string) Url::to('/'), PHP_URL_HOST);
        $body = (new IcsBuilder())->calendar($events, $name, $locale, $host, fn(Event $e) => self::eventUrl($e, $locale), SiteTimezone::current());

        return new Response($body, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    /** Absolute public URL of an event in the visitor's language, or null without a slug. */
    public static function eventUrl(Event $event, string $locale): ?string
    {
        $slug = $event->getSlug($locale);

        return $slug ? (string) Url::to('/events/' . $slug) : null;
    }

    public function event(string $slug = ''): Response
    {
        $finder = new EventFinder(app(EntityManagerInterface::class));
        $content = $finder->findBySlug($slug);
        if (!$content) {
            return new Response('Not found', 404);
        }
        $event = $content->getEvent();

        return $this->respond([$event], $event->getName(), preg_replace('/[^a-z0-9-]/i', '', $slug) . '.ics');
    }

    public function feed(): Response
    {
        $finder = new EventFinder(app(EntityManagerInterface::class));
        $name = (string) app('site')->getSite()->getSiteName();

        return $this->respond($finder->upcoming(self::FEED_LIMIT)->events, $name, 'events.ics');
    }
}
