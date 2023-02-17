<?php

namespace BarrelStrength\Sprout\forms\components\events;

use BarrelStrength\Sprout\forms\components\elements\FormElement;
use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use yii\base\Event;

class OnBeforeValidateSubmissionEvent extends Event
{
    /**
     * @var FormElement
     */
    public FormElement $form;

    /**
     * @var SubmissionElement
     */
    public SubmissionElement $submission;
}
