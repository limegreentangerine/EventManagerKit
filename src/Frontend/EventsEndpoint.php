<?php

declare(strict_types=1);

namespace EventManagerKit\Frontend;

use Concrete\Core\Http\Request;
use Doctrine\ORM\EntityManagerInterface;
use EventManagerKit\Translation\LocaleContext;
use Symfony\Component\HttpFoundation\{JsonResponse, Response};

/**
 * GET /ajax/events-manager/events
 *
 * Public, read-only, never cached: the block pages themselves may be cached
 * (Cloudflare, Concrete full page cache), so they ship an empty shell and
 * fetch events from here. Exclude this path from CDN caching.
 *
 * Returns {"html": "..."}: the inner markup of the block, rendered by the
 * same elements the theme can override.
 */
final class EventsEndpoint
{
    public const PATH = '/ajax/events-manager/events';

    public function events(): Response
    {
        $request = EndpointRequest::fromArray(
            app(Request::class)->query->all(),
            LocaleContext::siteLocales(),
            LocaleContext::default(),
        );
        if (!$request) {
            return $this->json(['error' => 'Invalid request.'], 400);
        }

        // Render in the visitor's language: t() and LocaleContext::active() follow this.
        \Concrete\Core\Localization\Localization::changeLocale($request->locale);

        $finder = new EventFinder(app(EntityManagerInterface::class));
        $html = $request->view === 'calendar' ? $this->calendar($finder, $request) : $this->list($finder, $request);

        return $this->json(['html' => $html]);
    }

    private function calendar(EventFinder $finder, EndpointRequest $request): string
    {
        $page = $finder->forMonth($request->query);

        return $this->render('frontend/calendar', [
            'query' => $request->query,
            'events' => $page->events,
            'hasMore' => $page->hasMore,
            'filters' => $request->showFilters ? $finder->filterOptions() : null,
            'locale' => $request->locale,
        ]);
    }

    private function list(EventFinder $finder, EndpointRequest $request): string
    {
        return $this->render('frontend/event_list', [
            'events' => $finder->upcoming($request->limit)->events,
            'locale' => $request->locale,
        ]);
    }

    private function render(string $element, array $data): string
    {
        ob_start();
        \View::element($element, $data, 'event_manager_kit');

        return (string) ob_get_clean();
    }

    private function json(array $data, int $status = 200): JsonResponse
    {
        $response = new JsonResponse($data, $status);
        // Events change in the dashboard at any time; no cache may keep this.
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
