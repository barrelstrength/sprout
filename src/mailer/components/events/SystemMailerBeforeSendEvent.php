<?php

namespace BarrelStrength\Sprout\mailer\components\events;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use yii\base\Event;

class SystemMailerBeforeSendEvent extends Event
{
    /**
     * The Email being sent
     */
    public EmailElement $email;
}
