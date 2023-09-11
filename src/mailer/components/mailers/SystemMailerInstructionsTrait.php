<?php

namespace BarrelStrength\Sprout\mailer\components\mailers;

use BarrelStrength\Sprout\mailer\audience\AudienceHelper;
use BarrelStrength\Sprout\mailer\components\elements\audience\AudienceElement;
use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\mailers\Mailer;
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
    public ?string $sender = null;

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

    protected ?SystemMailer $mailer = null;

    public function setMailer(?SystemMailer $mailer): void
    {
        $this->mailer = $mailer;
    }

    public function getMailer(): ?SystemMailer
    {
        return $this->mailer;
    }

    public function getSenderAsString(): ?string
    {
        if (!$this->fromName || !$this->fromEmail) {
            return null;
        }

        return $this->getFromName() . ' <' . $this->getFromEmail() . '>';
    }

    public function getSender(): mixed
    {
        $sender = $this->sender ?? $this->getSenderAsString();

        $senderAddress = Address::create($sender);

        return [
            $senderAddress->getAddress() => $senderAddress->getName(),
        ];
    }

    public function getFromName(): ?string
    {
        if (!$this->fromName) {
            return $this->fromName;
        }

        return App::parseEnv($this->fromName);
    }

    public function getFromEmail(): ?string
    {
        if (!$this->fromEmail) {
            return null;
        }

        return App::parseEnv($this->fromEmail);
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
        $rules[] = [['sender'], 'validateApprovedSender', 'when' => fn() => $this->sender !== null];

        $rules[] = ['replyToEmail', 'email', 'when' => fn() => $this->replyToEmail !== null];
        $rules[] = ['replyToEmail', 'validateApprovedReplyTo', 'when' => fn() => $this->replyToEmail !== null];

        $rules[] = [['recipients'], 'required', 'message' => Craft::t('sprout-module-mailer', '{attribute} in "To Field" cannot be blank.')];
        $rules[] = ['recipients', 'validateRecipients'];

        return $rules;
    }

    public function validateApprovedSender(): void
    {
        $mailer = $this->getMailer();

        if (!$mailer) {
            return;
        }

        $sender = $this->getSender();

        // Make sure the current sender is in the list of approved senders
        if (!$mailer->isApprovedSender(reset($sender), key($sender))) {
            $this->addError('sender', 'Sender is not in list of approved senders.');
        }
    }
    public function validateApprovedReplyTo(): void
    {
        $mailer = $this->getMailer();

        if (!$mailer) {
            return;
        }

        $fromEmail = $this->getFromEmail();
        $replyTo = $this->getReplyToEmail();

        // Make sure the current replyTo address is in the list of approved replyTos
        if (!$mailer->isApprovedReplyTo($replyTo, $fromEmail)) {
            $this->addError('replyToEmail', 'Reply To address is not in list of approved addresses.');
        }
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
