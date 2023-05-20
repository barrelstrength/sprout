<?php

namespace BarrelStrength\Sprout\transactional\components\elements\fieldlayoutelements;

use BarrelStrength\Sprout\transactional\components\emailtypes\TransactionalEmailEmailType;
use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseNativeField;

class SendRuleField extends BaseNativeField
{
    public bool $mandatory = true;

    public string $attribute = 'sendRule';

    protected function showLabel(): bool
    {
        return false;
    }

    protected function selectorLabel(): ?string
    {
        return Craft::t('sprout-module-mailer', 'Send Rule');
    }

    protected function inputHtml(ElementInterface $element = null, bool $static = false): ?string
    {
        $notificationEvent = TransactionalEmailEmailType::getNotificationEvent($element);

        return Craft::$app->getView()->renderTemplate('sprout-module-transactional/_components/elements/email/sendRule.twig', [
            'element' => $element,
            'notificationEvent' => $notificationEvent,
            'emailType' => $element->getEmailTypeSettings(),
        ]);
    }
}
