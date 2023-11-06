<?php

namespace BarrelStrength\Sprout\mailer\components\mailers;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\mailers\MailerInstructionsSettings;
use Craft;

abstract class SystemMailerInstructionsSettingsTestSettings extends MailerInstructionsSettings implements SystemMailerInstructionsInterface
{
    use SystemMailerInstructionsTrait;

    public function getSubjectLine(EmailElement $email): string
    {
        $prefix = Craft::t('sprout-module-mailer', '[Test]');

        return $prefix . ' ' . $email->subjectLine;
    }
}
