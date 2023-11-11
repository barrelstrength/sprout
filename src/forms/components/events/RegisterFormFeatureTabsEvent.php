<?php

namespace BarrelStrength\Sprout\forms\components\events;

use BarrelStrength\Sprout\forms\components\elements\FormElement;
use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use craft\models\FieldLayout;
use yii\base\Event;

class RegisterFormFeatureTabsEvent extends Event
{
    public FormElement $element;

    public FieldLayout $fieldLayout;

    public array $tabs = [];
}
