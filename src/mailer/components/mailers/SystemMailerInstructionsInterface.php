<?php

namespace BarrelStrength\Sprout\mailer\components\mailers;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\mailers\MailerInstructionsInterface;
use craft\elements\Asset;

interface SystemMailerInstructionsInterface extends MailerInstructionsInterface
{
    /**
     * Returns a compatible value for the From address
     *
     * @example 'From Name'
     * @example ['email@address.com' => 'From Name']
     */
    public function getSender(): mixed;

    /**
     * Gives the Mailer instructions a chance to modify the subject line
     */
    public function getSubjectLine(EmailElement $email): string;

    /**
     * Returns a Mailing List which must have a getRecipients method
     */
    public function getMailingList(EmailElement $email, array $templateVariables = []): mixed;

    /*
     * Returns an array of name/value pairs to be used as template variables
     *
     * @example [ 'object' => $object ] => {{ object.value }} in the template
     */
    public function getAdditionalTemplateVariables(EmailElement $email): array;

    /**
     * Returns an array of [[Asset]] Element models
     *
     * @return Asset[]
     */
    public function getMessageFileAttachments(EmailElement $email): array;
}
