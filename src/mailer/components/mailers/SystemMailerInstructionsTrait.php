<?php

namespace BarrelStrength\Sprout\mailer\components\mailers;

use BarrelStrength\Sprout\mailer\audience\AudienceHelper;
use BarrelStrength\Sprout\mailer\components\elements\audience\AudienceElement;
use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use Craft;
use craft\base\Element;
use craft\elements\db\AssetQuery;
use craft\fields\Assets;
use craft\helpers\App;
use Exception;
use Symfony\Component\Mime\Address;

trait SystemMailerInstructionsTrait
{
    /**
     * The submitted sender before we break it into $fromName and $fromEmail
     *
     * e.g. Name <email>
     */
    public ?string $sender = null;

    /**
     * The sender replyTo email, if different than the sender email
     */
    public ?string $replyToEmail = null;

    /**
     * A comma, delimited list of recipients (To email)
     */
    public ?string $recipients = null;

    /**
     * Array of Audience IDs
     */
    public ?array $audienceIds = null;

    public function getSenderAsString(): mixed
    {
        $sender = $this->getSender();

        return current($sender) . ' <' . key($sender) . '>' ?? null;
    }

    public function getSender(): mixed
    {
        $senderAddress = Address::create($this->sender);

        return [
            App::parseEnv($senderAddress->getAddress()) => App::parseEnv($senderAddress->getName()),
        ];
    }

    public function getReplyToEmail(): string|array
    {
        if (!$this->replyToEmail) {
            return $this->getSender();
        }

        return App::parseEnv($this->replyToEmail);
    }

    public function getRecipients(array $templateVariables = []): array
    {
        $stringRecipients = $this->recipients
            ? MailingListRecipient::stringToMailingListRecipientList($this->recipients)
            : [];

        $audienceRecipients = AudienceHelper::getAudienceRecipients(
            $this->audienceIds
        );

        $potentialRecipients = [...$stringRecipients, ...$audienceRecipients];

        $recipients = [];

        foreach ($potentialRecipients as $potentialRecipient) {
            if ($potentialRecipient->isDynamicEmail()) {
                // We only support object syntax for Email (not Name)
                $parsedEmail = Craft::$app->getView()->renderObjectTemplate(
                    $potentialRecipient->emailTemplateString, $templateVariables
                );

                $potentialRecipient = $this->updateDynamicRecipient($potentialRecipient, $parsedEmail);
            }

            $recipients[] = $potentialRecipient;
        }

        return $recipients;
    }

    public function updateDynamicRecipient(MailingListRecipient $recipient, $email): MailingListRecipient
    {
        try {
            $address = Address::create($email);
            $recipient->email = $address->getAddress();
        } catch (Exception) {
            $recipient->addError('email', 'Unable to parse dynamic email value into a valid email address.');
        }

        return $recipient;
    }

    public function getAudiences(): array
    {
        if (empty($this->audienceIds)) {
            return [];
        }

        $elements = AudienceElement::find()
            ->where(['in', 'sprout_audiences.id', $this->audienceIds])
            ->all();

        return $elements;
    }

    public function getMailingList(EmailElement $email, array $templateVariables = []): MailingList
    {
        // Prep Mailing List
        $mailingList = new MailingList();
        $mailingList->addRecipients($this->getRecipients($templateVariables));

        if ($mailingList->hasErrors()) {
            $email->addError('mailerInstructionsSettings', $mailingList->getErrors());
        }

        return $mailingList;
    }

    public function getAdditionalTemplateVariables(EmailElement $email): array
    {
        return $email->getEmailTypeSettings()->getAdditionalTemplateVariables();
    }

    public function getMessageFileAttachments(EmailElement $email): array
    {
        $emailTypeSettings = $email->getEmailTypeSettings();

        if (!$emailTypeSettings->enableFileAttachments) {
            return [];
        }

        $templateVariables = $this->getAdditionalTemplateVariables($email);

        // We only attach files that are identified in the event object
        $object = $templateVariables['object'] ?? null;

        if (!$object instanceof Element || !$object::hasContent()) {
            return [];
        }

        $assets = [];

        foreach ($object->getFieldLayout()->getCustomFields() as $field) {
            if ($field instanceof Assets) {
                $query = $object->{$field->handle};

                if ($query instanceof AssetQuery) {
                    $results = $query->all();

                    $assets = [...$assets, ...$results];
                }
            }
        }

        return $assets;
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
