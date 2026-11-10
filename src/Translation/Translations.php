<?php

declare(strict_types=1);

namespace EventManagerKit\Translation;

/**
 * Picks the best translation row for a locale.
 *
 * Order: exact locale (en_GB), same language (en_US -> en_GB), default locale,
 * first row available. Never returns blank when any translation exists.
 */
final class Translations
{
    /**
     * @template T of object
     * @param iterable<T> $rows objects exposing getLocale()
     * @return T|null
     */
    public static function pick(iterable $rows, string $locale, string $defaultLocale = 'en_GB'): ?object
    {
        $rows = is_array($rows) ? $rows : iterator_to_array($rows, false);

        foreach ([$locale, $defaultLocale] as $wanted) {
            foreach ($rows as $row) {
                if (strcasecmp($row->getLocale(), $wanted) === 0) {
                    return $row;
                }
            }
            foreach ($rows as $row) {
                if (self::language($row->getLocale()) === self::language($wanted)) {
                    return $row;
                }
            }
        }

        return $rows[0] ?? null;
    }

    private static function language(string $locale): string
    {
        return strtolower(preg_split('/[_-]/', $locale)[0]);
    }
}
