<?php

namespace BarrelStrength\Sprout\forms\formfields;

use Craft;

abstract class GroupLabel
{
    public const GROUP_COMMON = 'common';

    public const GROUP_REFERENCE = 'reference';

    public const GROUP_RELATIONS = 'relations';

    public const GROUP_LAYOUT = 'layout';

    public const GROUP_CUSTOM = 'custom';

    public static function label(string $name): string
    {
        return match ($name) {
            self::GROUP_COMMON => Craft::t('sprout-module-forms', 'Common Fields'),
            self::GROUP_REFERENCE => Craft::t('sprout-module-forms', 'Reference Fields'),
            self::GROUP_RELATIONS => Craft::t('sprout-module-forms', 'Relations Fields'),
            self::GROUP_LAYOUT => Craft::t('sprout-module-forms', 'Layout Fields'),
            self::GROUP_CUSTOM => Craft::t('sprout-module-forms', 'Custom Fields'),
        };
    }
}
