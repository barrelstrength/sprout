<?php

namespace BarrelStrength\Sprout\forms\formtypes;

use BarrelStrength\Sprout\core\components\linktypes\AbsoluteUrl;
use BarrelStrength\Sprout\core\components\linktypes\CurrentUrl;
use BarrelStrength\Sprout\core\components\linktypes\RelativeUrl;
use BarrelStrength\Sprout\forms\components\formtypes\DefaultFormType;
use BarrelStrength\Sprout\forms\components\formtypes\fieldlayoutelements\RedirectUrlField;
use BarrelStrength\Sprout\forms\FormsModule;
use Craft;
use craft\errors\MissingComponentException;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\fields\data\LinkData;
use craft\fields\Link;
use craft\fields\linktypes\BaseLinkType;
use craft\fields\linktypes\Category;
use craft\fields\linktypes\Entry;
use craft\helpers\Component;
use craft\helpers\ProjectConfig;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;

class LinkFieldLayoutFieldHelper
{
    public static function getLinkTypes(): array
    {
        $linkTypes = [
            'current-url' => CurrentUrl::class,
            'relative-url' => RelativeUrl::class,
            'absolute-url' => AbsoluteUrl::class,
            'category' => Category::class,
            'entry' => Entry::class,
        ];

        return $linkTypes;
    }

    public static function getLinkTypeClassById(string $linkTypeId): ?string
    {
        $linkTypes = self::getLinkTypes();

        return $linkTypes[$linkTypeId] ?? null;
    }

    public static function toFieldLayoutField(array $config): ?LinkData
    {
        $typeId = $config['type'];

        $value = $config[$typeId]['value'] // First scenario is POST data with all link types in it
            ?? $config['value']  // Second scenario is from the db, with only the saved value
            ?? null;
        $value = !empty(trim($value)) ? $value : '';

        $linkTypeClass = self::getLinkTypeClassById($typeId);
        $linkType = new $linkTypeClass();

        if (empty($value)) {
            return null;
        }

        return new LinkData($value, $linkType);
    }
}
