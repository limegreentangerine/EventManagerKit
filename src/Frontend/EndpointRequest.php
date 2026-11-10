<?php

declare(strict_types=1);

namespace EventManagerKit\Frontend;

/**
 * Parameters of the public events endpoint, validated.
 *
 * Calendar filters go through EventQuery (lenient, falls back to defaults).
 * What the block needs to render is checked strictly: an unknown view is
 * rejected, an unknown locale falls back to the site default, the list size
 * is capped.
 */
final class EndpointRequest
{
    public const VIEWS = ['calendar', 'list'];
    public const MAX_LIMIT = 50;
    public const DEFAULT_LIMIT = 5;

    private function __construct(
        public readonly string $view,
        public readonly string $locale,
        public readonly int $limit,
        public readonly bool $showFilters,
        public readonly EventQuery $query,
    ) {}

    /**
     * @param  array<string,mixed> $input       raw query string
     * @param  list<string>        $siteLocales locales the site is translated into
     * @return self|null           null when the request is not one we serve (answer 400)
     */
    public static function fromArray(array $input, array $siteLocales, string $defaultLocale): ?self
    {
        $view = $input['view'] ?? null;
        if (!is_string($view) || !in_array($view, self::VIEWS, true)) {
            return null;
        }

        $locale = $input['locale'] ?? null;
        if (!is_string($locale) || !in_array($locale, $siteLocales, true)) {
            $locale = $defaultLocale;
        }

        $limit = $input['limit'] ?? null;
        $limit = is_string($limit) && ctype_digit($limit) ? max(1, min(self::MAX_LIMIT, (int) $limit)) : self::DEFAULT_LIMIT;

        return new self($view, $locale, $limit, ($input['filters'] ?? null) === '1', EventQuery::fromArray($input));
    }
}
