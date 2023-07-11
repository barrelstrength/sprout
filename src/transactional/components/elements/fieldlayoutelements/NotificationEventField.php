<?php

namespace BarrelStrength\Sprout\transactional\components\elements\fieldlayoutelements;

use BarrelStrength\Sprout\core\twig\TemplateHelper;
use BarrelStrength\Sprout\transactional\components\emailtypes\TransactionalEmailEmailType;
use BarrelStrength\Sprout\transactional\TransactionalModule;
use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseNativeField;

class NotificationEventField extends BaseNativeField
{
    public bool $mandatory = true;

    public string $attribute = 'notificationEvent';

    public bool $required = true;

    protected function showLabel(): bool
    {
        return false;
    }

    protected function selectorLabel(): ?string
    {
        return Craft::t('sprout-module-mailer', 'Event');
    }

    protected function inputHtml(ElementInterface $element = null, bool $static = false): ?string
    {
        $events = TransactionalModule::getInstance()->notificationEvents->getNotificationEvents();

        $eventOptions = TemplateHelper::optionsFromComponentTypes($events);

        $emailTypeSettings = $element->getEmailTypeSettings();
        $notificationEvent = $emailTypeSettings->getNotificationEvent($element);

        return Craft::$app->getView()->renderTemplate('sprout-module-transactional/_components/elements/email/events.twig', [
            'notificationEvent' => $notificationEvent,
            'events' => $events,
            'eventOptions' => $eventOptions,
            'static' => $static,
        ]);
    }
}
