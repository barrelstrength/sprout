<?php

namespace BarrelStrength\Sprout\mailer\components\mailers;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\mailers\BaseMailerInstructions;
use BarrelStrength\Sprout\mailer\mailers\SystemMailerInstructionsInterface;

class SystemMailerInstructionsTestSettings extends BaseMailerInstructions implements SystemMailerInstructionsInterface
{
    /**
     * The sender replyTo email, if different than the sender email
     */
    public string $replyToEmail = '';

    /**
     * Comma-delimited list of recipients
     */
    public ?string $recipients = null;

    public function getRecipients(): array
    {
        if (!$this->recipients) {
            return [];
        }

        return MailingListRecipient::stringToMailingListRecipientList($this->recipients);
    }

    public function beforeSend(EmailElement $email): void
    {
        $emailTypeSettings = $email->getEmailTypeSettings();

        $emailTypeSettings->addAdditionalTemplateVariables(
            'object', $emailTypeSettings->getMockObjectVariable($email)
        );

        parent::beforeSend($email);
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['sender', 'replyToEmail', 'recipients'], 'required'];
        $rules[] = ['sender', 'validateSender'];
        $rules[] = ['replyToEmail', 'validateReplyToEmail'];
        $rules[] = ['recipients', 'validateRecipients'];

        return $rules;
    }

    public function validateSender(): void
    {
        // Confirm approved value in mailer?
    }

    public function validateReplyToEmail(): void
    {
        // Confirm approved value in mailer?
    }

    public function validateRecipients(): void
    {
        if (!$this->recipients) {
            return;
        }

        foreach ($this->getRecipients() as $recipient) {
            if ($recipient->hasErrors()) {
                $this->addError('recipient', $recipient->getFirstError('recipient'));
            }
        }
    }
}

