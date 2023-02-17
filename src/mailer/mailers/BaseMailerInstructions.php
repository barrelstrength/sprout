<?php

namespace BarrelStrength\Sprout\mailer\mailers;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\components\events\SystemMailerBeforeSendEvent;
use craft\base\Model;

class BaseMailerInstructions extends Model implements MailerInstructionsInterface
{
    use SystemMailerInstructionsTrait;

    public const EVENT_SYSTEM_MAILER_BEFORE_SEND = 'onSystemMailerBeforeSend';

    /**
     * Gives MailerInstructions the option to do something before an email is sent
     */
    public function beforeSend(EmailElement $email): void
    {
        $event = new SystemMailerBeforeSendEvent(['email' => $email]);
        $this->trigger(self::EVENT_SYSTEM_MAILER_BEFORE_SEND, $event);
    }
}
