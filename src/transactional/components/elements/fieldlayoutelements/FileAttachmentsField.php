<?php

namespace BarrelStrength\Sprout\transactional\components\elements\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseNativeField;

class FileAttachmentsField extends BaseNativeField
{
    public bool $mandatory = true;

    public string $attribute = 'enableFileAttachments';

    protected function showLabel(): bool
    {
        return false;
    }

    protected function selectorLabel(): ?string
    {
        return Craft::t('sprout-module-transactional', 'File Attachments');
    }

    protected function inputHtml(ElementInterface $element = null, bool $static = false): ?string
    {
        $emailTypeSettings = $element->getEmailTypeSettings();
        $notificationEvent = $emailTypeSettings->getNotificationEvent($element);

        return Craft::$app->getView()->renderTemplate('sprout-module-transactional/_components/elements/email/fileAttachments.twig', [
            'notificationEvent' => $notificationEvent,
            'emailType' => $element->getEmailTypeSettings(),
            'static' => $static,
        ]);
    }
}
