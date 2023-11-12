<?php

namespace BarrelStrength\Sprout\mailer\mailers;

use craft\base\Model;

abstract class MailerInstructionsSettings extends Model implements MailerInstructionsInterface
{
    protected ?MailerInterface $mailer = null;

    public function setMailer(MailerInterface $mailer = null): void
    {
        $this->mailer = $mailer;
    }

    public function getMailer(): ?MailerInterface
    {
        return $this->mailer;
    }
}
