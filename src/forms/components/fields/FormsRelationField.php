<?php

namespace BarrelStrength\Sprout\forms\components\fields;

use BarrelStrength\Sprout\forms\components\elements\db\FormElementQuery;
use BarrelStrength\Sprout\forms\components\elements\FormElement;
use Craft;
use craft\fields\BaseRelationField;

class FormsRelationField extends BaseRelationField
{
    public static function displayName(): string
    {
        return Craft::t('sprout-module-forms', 'Forms (Sprout)');
    }

    public static function defaultSelectionLabel(): string
    {
        return Craft::t('sprout-module-forms', 'Add a form');
    }

    public static function valueType(): string
    {
        return FormElementQuery::class;
    }

    public static function elementType(): string
    {
        return FormElement::class;
    }
}
