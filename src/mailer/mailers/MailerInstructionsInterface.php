<?php

namespace BarrelStrength\Sprout\mailer\mailers;

/**
 * All Mailers Instructions Settings must implement MailerInstructionsInterface
 * so the Email knows its dealing with the Mailer settings but we assume nothing
 * about what a mailer might need to accomplish its goals.
 */
interface MailerInstructionsInterface
{
    public function setMailer(?Mailer $mailer): void;

    public function getMailer(): ?Mailer;
}
