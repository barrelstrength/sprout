<?php

namespace BarrelStrength\Sprout\mailer\components\mailers;

use Craft;
use craft\base\Model;

class MailingList extends Model
{
    /**
     * All recipients with valid email addresses
     *
     * @var MailingListRecipient[]
     */
    public array $recipients = [];

    /**
     * Recipients who don't have a valid email address
     *
     * @var MailingListRecipient[]
     */
    public array $invalidRecipients = [];

    /**
     * Recipients who are already on the list
     *
     * @var MailingListRecipient[]
     */
    public array $duplicateRecipients = [];

    /**
     * Recipients who did not get sent an email during the processing of a list
     *
     * @var MailingListRecipient[]
     */
    public array $failedRecipients = [];

    /**
     * Recipients who have been sent an email during the processing of a list
     *
     * @var MailingListRecipient[]
     */
    public array $processedRecipients = [];

    /**
     * Adds a MailingListRecipient model to the recipients or invalidRecipients array
     *
     * @var MailingListRecipient[] $recipients
     */
    public function addRecipients(array $recipients = []): void
    {
        foreach ($recipients as $recipient) {
            if ($recipient->hasErrors()) {
                $this->invalidRecipients[] = $recipient;
                continue;
            }

            if (isset($this->recipients[$recipient->email])) {
                $this->duplicateRecipients[] = $recipient;
                continue;
            }

            $this->recipients[$recipient->email] = $recipient;
        }
    }

    public function markAsProcessed(MailingListRecipient $recipient): void
    {
        $this->processedRecipients[$recipient->email] = $recipient;
    }

    public function markAsFailed(MailingListRecipient $recipient): void
    {
        $this->failedRecipients[$recipient->email] = $recipient;
    }

    public function getRecipients(): array
    {
        return $this->recipients;
    }

    public function getInvalidRecipients(): array
    {
        return $this->invalidRecipients;
    }

    public function getProcessedRecipients(): array
    {
        return $this->processedRecipients;
    }

    public function getFailedRecipients(): array
    {
        return $this->failedRecipients;
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['invalidRecipients'], 'validateInvalidRecipients'];
        $rules[] = [['duplicateRecipients'], 'validateDuplicateRecipients'];
        $rules[] = [['failedRecipients'], 'validateFailedRecipients'];

        return $rules;
    }

    public function validateInvalidRecipients(): void
    {
        if (!empty($this->invalidRecipients)) {
            $message = Craft::t('sprout-module-mailer', 'Mailing list includes invalid recipients.');
            $this->addError('invalidRecipients', $message);
        }
    }

    public function validateDuplicateRecipients(): void
    {
        if (!empty($this->duplicateRecipients)) {
            $message = Craft::t('sprout-module-mailer', 'Mailing list includes duplicate recipients.');
            $this->addError('duplicateRecipients', $message);
        }
    }

    public function validateFailedRecipients(): void
    {
        if (!empty($this->failedRecipients)) {
            $message = Craft::t('sprout-module-mailer', 'Mailing list includes failed recipients.');
            $this->addError('failedRecipients', $message);
        }
    }
}
