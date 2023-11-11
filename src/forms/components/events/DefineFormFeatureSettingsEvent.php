<?php

namespace BarrelStrength\Sprout\forms\components\events;

use BarrelStrength\Sprout\forms\components\elements\FormElement;
use BarrelStrength\Sprout\forms\formtypes\FormType;
use craft\models\FieldLayout;
use yii\base\Event;

class DefineFormFeatureSettingsEvent extends Event
{
    public FormType $formType;

    public array $featureSettings = [];
}
