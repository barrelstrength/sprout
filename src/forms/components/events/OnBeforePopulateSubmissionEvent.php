<?php

namespace BarrelStrength\Sprout\forms\components\events;

use BarrelStrength\Sprout\forms\components\elements\FormElement;
use yii\base\Event;

class OnBeforePopulateSubmissionEvent extends Event
{
    /**
     * @var FormElement
     */
    public FormElement $form;
}
