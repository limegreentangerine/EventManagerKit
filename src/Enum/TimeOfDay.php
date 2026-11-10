<?php

declare(strict_types=1);

namespace EventManagerKit\Enum;

enum TimeOfDay: string
{
    public static function fromHour(int $hour): self
    {
        return match (true) {
            $hour < 12 => self::Morning,
            $hour < 17 => self::Afternoon,
            default => self::Evening,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Morning => t('Morning'),
            self::Afternoon => t('Afternoon'),
            self::Evening => t('Evening'),
        };
    }
    case Morning = 'morning';
    case Afternoon = 'afternoon';
    case Evening = 'evening';
}
