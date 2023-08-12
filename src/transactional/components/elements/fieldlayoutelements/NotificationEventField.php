<?php

namespace BarrelStrength\Sprout\transactional\components\elements\fieldlayoutelements;

use BarrelStrength\Sprout\core\helpers\ComponentHelper;
use BarrelStrength\Sprout\core\twig\TemplateHelper;
use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\transactional\TransactionalModule;
use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseNativeField;
use yii\base\InvalidArgumentException;

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
        if (!$element instanceof EmailElement) {
            throw new InvalidArgumentException('Notification Event field can only be used in Email field layouts.');
        }

        $events = TransactionalModule::getInstance()->notificationEvents->getNotificationEventTypes();
        $eventOptions = TemplateHelper::optionsFromComponentTypes($events);

        $emailTypeSettings = $element->getEmailType();
        $notificationEvent = $emailTypeSettings->getNotificationEvent($element);

        return Craft::$app->getView()->renderTemplate('sprout-module-transactional/_components/elements/email/notificationEvents.twig', [
            'notificationEvent' => $notificationEvent,
            'events' => ComponentHelper::typesToInstances($events),
            'eventOptions' => $eventOptions,
            'static' => $static,
        ]);
    }
}
