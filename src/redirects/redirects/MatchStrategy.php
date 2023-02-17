<?php

namespace BarrelStrength\Sprout\redirects\redirects;

use Craft;

abstract class MatchStrategy
{
    public const EXACT_MATCH = 'exactMatch';

    public const REGEX_MATCH = 'regExMatch';

    public static function values(): array
    {
        return [
            self::EXACT_MATCH,
            self::REGEX_MATCH,
        ];
    }

    public static function options(): array
    {
        return [
            self::EXACT_MATCH => Craft::t('sprout-module-redirects', 'Exact Match'),
            self::REGEX_MATCH => Craft::t('sprout-module-redirects', 'Regex Match'),
        ];
    }
}
