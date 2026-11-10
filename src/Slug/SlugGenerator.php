<?php

declare(strict_types=1);

namespace EventManagerKit\Slug;

use Transliterator;

/**
 * URL slugs: lowercase ASCII words joined by single hyphens.
 */
final class SlugGenerator
{
    public const PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    /**
     * "Digwyddiad Ŵyl y Gwanwyn!" => "digwyddiad-wyl-y-gwanwyn". Falls back to
     * "event" when nothing usable is left (e.g. a name made only of symbols).
     */
    public static function slugify(string $text): string
    {
        $text = Transliterator::create('Any-Latin; Latin-ASCII; Lower()')?->transliterate($text) ?? strtolower($text);
        $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', $text), '-');

        return $slug === '' ? 'event' : substr($slug, 0, 200);
    }

    public static function isValid(string $slug): bool
    {
        return strlen($slug) <= 200 && preg_match(self::PATTERN, $slug) === 1;
    }

    /**
     * First of base, base-2, base-3... that is not taken.
     *
     * @param callable(string):bool $taken
     */
    public static function unique(string $base, callable $taken): string
    {
        $slug = $base;
        for ($i = 2; $taken($slug); $i++) {
            $slug = $base . '-' . $i;
        }

        return $slug;
    }
}
