<?php

namespace BarrelStrength\Sprout\mailer\components\mailers;

use BarrelStrength\Sprout\mailer\components\elements\audience\AudienceElement;
use BarrelStrength\Sprout\mailer\MailerModule;
use BarrelStrength\Sprout\mailer\mailers\BaseMailerInstructions;
use BarrelStrength\Sprout\mailer\mailers\SystemMailerInstructionsInterface;
use Craft;
use Exception;
use Symfony\Component\Mime\Address;

class SystemMailerInstructionsSettings extends BaseMailerInstructions implements SystemMailerInstructionsInterface
{
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

    public function getRecipients(array $templateVariables = []): array
    {
        if (!$this->recipients) {
            return [];
        }

        $stringRecipients = MailingListRecipient::stringToMailingListRecipientList($this->recipients);

        $audienceRecipients = MailerModule::getInstance()->audiences->getAudienceRecipients(
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
}

