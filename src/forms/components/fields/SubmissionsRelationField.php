<?php

namespace BarrelStrength\Sprout\forms\components\fields;

use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use Craft;
use craft\fields\BaseRelationField;

class SubmissionsRelationField extends BaseRelationField
{
    public static function displayName(): string
    {
        return Craft::t('sprout-module-forms', 'Submissions (Sprout)');
    }

    public static function defaultSelectionLabel(): string
    {
        return Craft::t('sprout-module-forms', 'Add a submission');
    }

    public static function elementType(): string
    {
        return SubmissionElement::class;
    }
}
