<?php

declare(strict_types=1);

namespace EventManagerKit\Time;

use DateTimeZone;
use DateTimeImmutable;

/**
 * Events are stored as wall-clock times with no zone; they mean "local time
 * in the site's timezone". This puts the zone back when one is needed
 * (ICS, schema.org, web calendar links).
 */
final class SiteTimezone
{
    /** The timezone configured for the current Concrete site, else the PHP default. */
    public static function current(): DateTimeZone
    {
        try {
            return new DateTimeZone((string) app('site')->getSite()->getTimezone());
        } catch (\Throwable) {
            return new DateTimeZone(date_default_timezone_get());
        }
    }

    /** Same wall-clock time, interpreted in $zone. */
    public static function localise(DateTimeImmutable $date, DateTimeZone $zone): DateTimeImmutable
    {
        return new DateTimeImmutable($date->format('Y-m-d H:i:s'), $zone);
    }

    /** The instant as UTC, e.g. 20261120T100000Z, for ICS and web calendar links. */
    public static function utc(DateTimeImmutable $date, DateTimeZone $zone): string
    {
        return self::localise($date, $zone)->setTimezone(new DateTimeZone('UTC'))->format('Ymd\THis\Z');
    }
}
