<?php

namespace BarrelStrength\Sprout\mailer\mailers;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\components\mailers\MailingList;
use Craft;
use craft\base\Element;
use craft\elements\db\AssetQuery;
use craft\fields\Assets;
use craft\helpers\App;
use Symfony\Component\Mime\Address;

trait SystemMailerInstructionsTrait
{
    /**
     * The submitted sender before we break it into $fromName and $fromEmail
     *
     * e.g. Name <email>
     */
    public ?string $sender = null;

    public function getSubjectLine(EmailElement $email): string
    {
        $prefix = Craft::t('sprout-module-mailer', '[Test]');

        return $prefix . ' ' . $email->subjectLine;
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
}
