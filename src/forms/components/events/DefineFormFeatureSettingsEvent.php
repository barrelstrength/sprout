<?php

namespace BarrelStrength\Sprout\forms\components\events;

use BarrelStrength\Sprout\forms\formtypes\FormType;
use yii\base\Event;

class DefineFormFeatureSettingsEvent extends Event
{
    public FormType $formType;

    public array $featureSettings = [];
}
