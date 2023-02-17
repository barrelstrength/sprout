<?php

namespace BarrelStrength\Sprout\redirects\redirects;

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
}
