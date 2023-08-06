<?php

namespace BarrelStrength\Sprout\transactional\components\mailers;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\components\mailers\SystemMailerInstructionsSettingsTestSettings;

class TransactionalMailerInstructionsTestSettings extends SystemMailerInstructionsSettingsTestSettings
{
    public function getAdditionalTemplateVariables(EmailElement $email): array
    {
        $emailTypeSettings = $email->getEmailType();
        $notificationEvent = $emailTypeSettings->getNotificationEvent($email);

        $emailTypeSettings->addAdditionalTemplateVariables(
            $notificationEvent->getMockEventVariables()
        );

        return $emailTypeSettings->getAdditionalTemplateVariables();
    }
}

