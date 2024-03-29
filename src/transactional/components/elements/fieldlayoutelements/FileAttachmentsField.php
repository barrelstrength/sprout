<?php

namespace BarrelStrength\Sprout\transactional\components\elements\fieldlayoutelements;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\transactional\components\emailvariants\TransactionalEmailVariant;
use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseNativeField;
use yii\base\InvalidArgumentException;

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
        if (!$element instanceof EmailElement) {
            throw new InvalidArgumentException('File Attachments field can only be used in Email field layouts.');
        }

        // @todo - Assumes specific Email Variant. Delegate responsibility of defining this field to the Email Variant class.
        /** @var TransactionalEmailVariant $emailVariantSettings */
        $emailVariantSettings = $element->getEmailVariant();
        $notificationEvent = $emailVariantSettings->getNotificationEvent($element);

        return Craft::$app->getView()->renderTemplate('sprout-module-transactional/_components/elements/email/fileAttachments.twig', [
            'notificationEvent' => $notificationEvent,
            'emailVariant' => $element->getEmailVariant(),
            'static' => $static,
        ]);
    }
}
