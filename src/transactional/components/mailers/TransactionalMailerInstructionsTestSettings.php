<?php

namespace BarrelStrength\Sprout\transactional\components\mailers;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\components\mailers\SystemMailerInstructionsSettingsTestSettings;

class TransactionalMailerInstructionsTestSettings extends SystemMailerInstructionsSettingsTestSettings
{
    public function getAdditionalTemplateVariables(EmailElement $email): array
    {
        $emailVariantSettings = $email->getEmailVariant();
        $notificationEvent = $emailVariantSettings->getNotificationEvent($email);

        $emailVariantSettings->addAdditionalTemplateVariables(
            $notificationEvent->getMockEventVariables()
        );

        return $emailVariantSettings->getAdditionalTemplateVariables();
    }
}

