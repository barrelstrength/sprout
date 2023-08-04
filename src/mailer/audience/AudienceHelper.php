<?php

namespace BarrelStrength\Sprout\mailer\audience;

use BarrelStrength\Sprout\mailer\components\elements\audience\AudienceElement;

class AudienceHelper
{
    public static function getAudienceRecipients($audienceIds): array
    {
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
