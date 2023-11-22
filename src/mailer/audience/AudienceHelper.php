<?php

namespace BarrelStrength\Sprout\mailer\audience;

use BarrelStrength\Sprout\mailer\components\elements\audience\AudienceElement;
use BarrelStrength\Sprout\mailer\components\mailers\MailingListRecipient;
use BarrelStrength\Sprout\mailer\MailerModule;

class AudienceHelper
{
    /**
     * @return MailingListRecipient[]
     */
    public static function getAudienceRecipients($audienceIds): array
    {
        $settings = MailerModule::getInstance()->getSettings();

        if (!$settings->enableAudiences) {
            return [];
        }

        if (empty($audienceIds)) {
            return [];
        }

        $recipients = [];

        foreach ($audienceIds as $audienceId) {
            /** @var AudienceElement $audience */
            $audience = AudienceElement::findOne($audienceId);

            $recipients = [...$recipients, ...$audience->getAudienceType()->getRecipients()];
        }

        return $recipients;
    }
}
