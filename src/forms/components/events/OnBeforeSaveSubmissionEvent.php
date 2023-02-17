<?php

namespace BarrelStrength\Sprout\forms\components\events;

use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use yii\base\Event;

class OnBeforeSaveSubmissionEvent extends Event
{
    /**
     * The Submission being saved
     *
     * @var SubmissionElement
     */
    public SubmissionElement $submission;

    /**
     * Set isValid to false to stop the Submission from being saved.
     */
    public bool $isValid = true;

    /**
     * Any errors defined on the Event will be added to the Submission model if isValid is set to false
     */
    public array $errors = [];
}
