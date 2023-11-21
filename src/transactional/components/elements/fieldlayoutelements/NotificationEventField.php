<?php

namespace BarrelStrength\Sprout\transactional\components\elements\fieldlayoutelements;

use BarrelStrength\Sprout\core\helpers\ComponentHelper;
use BarrelStrength\Sprout\core\Sprout;
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

        $emailVariantSettings = $element->getEmailVariant();
        $notificationEvent = $emailVariantSettings->getNotificationEvent($element);

        // This JS works on the Element Editor page but not in the Slideout Editor, which is okay for now
        Sprout::getInstance()->vite->register('transactional/NotificationEvents.js', false);

        return Craft::$app->getView()->renderTemplate('sprout-module-transactional/_components/elements/email/notificationEvents.twig', [
            'notificationEvent' => $notificationEvent,
            'events' => ComponentHelper::typesToInstances($events),
            'eventOptions' => $eventOptions,
            'static' => $static,
        ]);
    }
}
