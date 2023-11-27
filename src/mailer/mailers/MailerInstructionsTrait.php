<?php

namespace BarrelStrength\Sprout\mailer\mailers;

trait MailerInstructionsTrait
{
    protected ?Mailer $mailer = null;

    public function setMailer(?Mailer $mailer): void
    {
        $this->mailer = $mailer;
    }

    public function getMailer(): ?Mailer
    {
        return $this->mailer;
    }
}
