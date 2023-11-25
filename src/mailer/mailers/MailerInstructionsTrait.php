<?php

namespace BarrelStrength\Sprout\mailer\mailers;

use BarrelStrength\Sprout\datastudio\components\elements\DataSetElement;
use BarrelStrength\Sprout\mailer\components\mailers\SystemMailer;
use craft\base\Plugin;

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
