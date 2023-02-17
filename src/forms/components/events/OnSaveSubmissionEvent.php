<?php

namespace BarrelStrength\Sprout\forms\components\events;

use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use yii\base\Event;

class OnSaveSubmissionEvent extends Event
{
    public SubmissionElement $submission;

    public bool $isNewSubmission = true;
}
