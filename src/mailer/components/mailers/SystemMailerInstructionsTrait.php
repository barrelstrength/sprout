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
     * e.g. ['fromName' => Name, 'fromEmail' => email]
     */
    public array $sender = [];

    public ?string $fromName = null;

    public ?string $fromEmail = null;

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

    public function getSenderAsString(): ?string
    {
        if (!$this->fromName || !$this->fromEmail) {
            return null;
        }

        return App::parseEnv($this->fromName) . ' <' . App::parseEnv($this->fromEmail) . '>';
    }

    public function getSender(): mixed
    {
        $senderAddress = Address::create($this->getSenderAsString());

        return [
            $senderAddress->getAddress() => $senderAddress->getName(),
        ];
    }

    public function getReplyToEmail(): string|array
    {
        if (!$this->replyToEmail) {
            return App::parseEnv($this->fromEmail);
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
        return $email->getEmailVariant()->getAdditionalTemplateVariables();
    }

    public function getMessageFileAttachments(EmailElement $email): array
    {
        $emailVariantSettings = $email->getEmailVariant();

        if (!$emailVariantSettings->enableFileAttachments) {
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

        $rules[] = ['fromName', 'required', 'when' => fn() => $this->fromName !== null];
        $rules[] = ['fromEmail', 'required', 'when' => fn() => $this->fromEmail !== null];
        $rules[] = ['fromEmail', 'email', 'when' => fn() => $this->fromEmail !== null];
        $rules[] = ['replyToEmail', 'email', 'when' => fn() => $this->replyToEmail !== null];

        $rules[] = [['recipients'], 'required', 'message' => Craft::t('sprout-module-mailer', '{attribute} in "To Field" cannot be blank.')];
        $rules[] = ['recipients', 'validateRecipients'];

        return $rules;
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
