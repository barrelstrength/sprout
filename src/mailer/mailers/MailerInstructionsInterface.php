<?php

namespace BarrelStrength\Sprout\mailer\mailers;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use craft\base\Model;

/**
 * All Mailers Instructions Settings must implement MailerInstructionsInterface
 * so the Email knows its dealing with the Mailer settings but we assume nothing
 * about what a mailer might need to accomplish its goals.
 *
 * @mixin Model
 */
interface MailerInstructionsInterface
{
    /**
     * Gives the Mailer instructions a chance to modify the subject line
     */
    public function getSubjectLine(EmailElement $email): string;
}
