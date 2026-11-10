<?php

declare(strict_types=1);

namespace EventManagerKit\Translation;

use Throwable;

/**
 * The locale to display in, and the site's default locale to fall back to.
 * Outside a booted Concrete site (unit tests) both resolve to en_GB.
 */
final class LocaleContext
{
    public const FALLBACK = 'en_GB';

    public static function active(): string
    {
        try {
            return \Concrete\Core\Localization\Localization::activeLocale() ?: self::FALLBACK;
        } catch (Throwable) {
            return self::FALLBACK;
        }
    }

    public static function default(): string
    {
        try {
            $locale = app('site')->getSite()->getDefaultLocale();

            return $locale ? $locale->getLocale() : self::FALLBACK;
        } catch (Throwable) {
            return self::FALLBACK;
        }
    }

    /**
     * Locales the site is translated into, default locale first.
     *
     * @return list<string>
     */
    public static function siteLocales(): array
    {
        $locales = [self::default()];

        try {
            foreach (app('site')->getSite()->getLocales() as $locale) {
                $locales[] = $locale->getLocale();
            }
        } catch (Throwable) {
        }

        return array_values(array_unique($locales));
    }
}
