<?php

declare(strict_types=1);

namespace EventManagerKit;

use Concrete\Core\Config\Repository\Liaison;

/**
 * The per-site switch for activity features (age groups, price, summary).
 * Stored in the package file config, like Package::getFileConfig().
 */
final class Activities
{
    public const KEY = 'activities.enabled';
    public const AGE_GROUPS_PATH = '/dashboard/event_manager/age_groups';

    private static function config(): Liaison
    {
        return new Liaison(app('config'), 'event_manager_kit');
    }

    public static function enabled(): bool
    {
        return (bool) self::config()->get(self::KEY, false);
    }

    public static function setEnabled(bool $enabled): void
    {
        self::config()->save(self::KEY, $enabled);
    }
}
