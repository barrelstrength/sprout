<?php

namespace BarrelStrength\Sprout\transactional\components\mailers;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\components\mailers\SystemMailer;
use BarrelStrength\Sprout\mailer\components\mailers\SystemMailerInstructionsSettings;
use BarrelStrength\Sprout\transactional\components\emailvariants\TransactionalEmailVariant;

/**
 * @property SystemMailer $mailer
 */
class TransactionalMailerInstructionsSettings extends SystemMailerInstructionsSettings
{
    public function getAdditionalTemplateVariables(EmailElement $email): array
    {
        /** @var TransactionalEmailVariant $emailVariantSettings */
        $emailVariantSettings = $email->getEmailVariant();
        $notificationEvent = $emailVariantSettings->getNotificationEvent($email);

        $emailVariantSettings->addAdditionalTemplateVariables(
            $notificationEvent->getEventVariables()
        );

        return $emailVariantSettings->getAdditionalTemplateVariables();
    }

    public function validate($attributeNames = null, $clearErrors = true): bool
    {
        if ($this->mailer->senderEditBehavior === SystemMailer::SENDER_BEHAVIOR_CUSTOM) {
            $this->setScenario(SystemMailer::SENDER_BEHAVIOR_CUSTOM);
        }

        return parent::validate($attributeNames, $clearErrors);
    }
}
