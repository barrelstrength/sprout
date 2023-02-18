<?php

namespace BarrelStrength\Sprout\redirects\redirects;

use Craft;

abstract class StatusCode
{
    public const PERMANENT = 301;

    public const TEMPORARY = 302;

    public const PAGE_NOT_FOUND = 404;

    public static function values(): array
    {
        return [
            self::PERMANENT,
            self::TEMPORARY,
            self::PAGE_NOT_FOUND,
        ];
    }

    public static function options(): array
    {
        return [
            self::PERMANENT => self::PERMANENT . ' - ' . Craft::t('sprout-module-redirects', 'Permanent'),
            self::TEMPORARY => self::TEMPORARY . ' - ' . Craft::t('sprout-module-redirects', 'Temporary'),
            self::PAGE_NOT_FOUND => self::PAGE_NOT_FOUND . ' - ' . Craft::t('sprout-module-redirects', 'Page Not Found'),
        ];
    }
}
