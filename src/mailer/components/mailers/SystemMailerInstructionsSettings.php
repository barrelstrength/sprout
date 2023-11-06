<?php

namespace BarrelStrength\Sprout\mailer\components\mailers;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\mailers\MailerInstructionsSettings;

abstract class SystemMailerInstructionsSettings extends MailerInstructionsSettings implements SystemMailerInstructionsInterface
{
    use SystemMailerInstructionsTrait;

    public function getSubjectLine(EmailElement $email): string
    {
        return $email->subjectLine;
    }
}
