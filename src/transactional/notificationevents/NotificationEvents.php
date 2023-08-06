<?php

namespace BarrelStrength\Sprout\transactional\notificationevents;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\transactional\components\conditions\IsNewEntryConditionRule;
use BarrelStrength\Sprout\transactional\components\conditions\IsUpdatedEntryConditionRule;
use BarrelStrength\Sprout\transactional\components\elements\TransactionalEmailElement;
use BarrelStrength\Sprout\transactional\components\emailtypes\TransactionalEmailEmailType;
use BarrelStrength\Sprout\transactional\components\notificationevents\EntryDeletedNotificationEvent;
use BarrelStrength\Sprout\transactional\components\notificationevents\EntrySavedNotificationEvent;
use BarrelStrength\Sprout\transactional\components\notificationevents\ManualNotificationEvent;
use BarrelStrength\Sprout\transactional\components\notificationevents\UserActivatedNotificationEvent;
use BarrelStrength\Sprout\transactional\components\notificationevents\UserCreatedNotificationEvent;
use BarrelStrength\Sprout\transactional\components\notificationevents\UserDeletedNotificationEvent;
use BarrelStrength\Sprout\transactional\components\notificationevents\UserLoggedInNotificationEvent;
use BarrelStrength\Sprout\transactional\components\notificationevents\UserUpdatedNotificationEvent;
use BarrelStrength\Sprout\transactional\TransactionalModule;
use Craft;
use craft\base\Component;
use craft\base\Element;
use craft\elements\Entry;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterConditionRuleTypesEvent;
use craft\helpers\Json;
use yii\base\Event;

class NotificationEvents extends Component
{
    /**
     * Represents a placeholder, non-event when a Notification is managed manually
     */
    public const EVENT_MANUAL_NOTIFICATION_NON_EVENT = 'onManualNotificationNonEvent';

    /**
     * Registers any available NotificationEvent classes
     */
    public const INTERNAL_SPROUT_EVENT_REGISTER_NOTIFICATION_EVENTS = 'registerInternalSproutNotificationEvents';

    public const EVENT_REGISTER_NOTIFICATION_EVENTS = 'registerSproutNotificationEvents';

    private ?array $_notificationEventsTypes = null;

    /**
     * Returns all the available Notification Event Types
     */
    public function getNotificationEventTypes(): array
    {
        if ($this->_notificationEventsTypes) {
            return $this->_notificationEventsTypes;
        }

        $internalNotificationEventsTypes[] = EntrySavedNotificationEvent::class;

        if (TransactionalModule::isPro()) {
            $internalNotificationEventsTypes = [
                EntryDeletedNotificationEvent::class,
                ManualNotificationEvent::class,
                UserActivatedNotificationEvent::class,
                UserDeletedNotificationEvent::class,
                UserLoggedInNotificationEvent::class,
                UserCreatedNotificationEvent::class,
                UserUpdatedNotificationEvent::class,
            ];
        }

        $internalEvent = new RegisterComponentTypesEvent([
            'types' => $internalNotificationEventsTypes,
        ]);

        $this->trigger(self::INTERNAL_SPROUT_EVENT_REGISTER_NOTIFICATION_EVENTS, $internalEvent);

        $proEvent = new RegisterComponentTypesEvent([
            'types' => $internalNotificationEventsTypes,
        ]);

        if (TransactionalModule::isPro()) {
            $this->trigger(self::EVENT_REGISTER_NOTIFICATION_EVENTS, $proEvent);
        }

        // Get available Notification Event Types for current edition
        $availableNotificationEventTypes = TransactionalModule::isPro()
            ? array_merge($internalEvent->types, $proEvent->types)
            : $internalEvent->types;

        $types = array_combine($availableNotificationEventTypes, $availableNotificationEventTypes);

        uasort($types, static function($a, $b): int {
            /**
             * @var $a NotificationEvent
             * @var $b NotificationEvent
             */
            return $a::displayName() <=> $b::displayName();
        });

        return $types;
    }

    /**
     * Dynamically registers a handler for each Event that is referenced in a Notification Event
     *
     * When triggered, the registered event will hand off the Event information to a Sprout
     * Notification Event handler so Sprout can process that information and trigger emails
     */
    public function registerNotificationEventHandlers(): void
    {
        if (!$this->isNotificationEventContext()) {
            return;
        }

        $enabledEmailEventTypes = $this->getEnabledNotificationEventTypes();

        foreach ($enabledEmailEventTypes as $notificationEventType) {

            if ($notificationEventType instanceof ManualNotificationEvent) {
                continue;
            }

            // @todo - events get registered multiple times...
            Event::on(
                $notificationEventType::getEventClassName(),
                $notificationEventType::getEventName(),
                static function($event) use ($notificationEventType) {
                    TransactionalModule::getInstance()->notificationEvents->handleNotificationEvent(
                        $notificationEventType,
                        $event
                    );
                }
            );
        }
    }

    public function handleNotificationEvent(string $notificationEventType, Event $event): void
    {
        if (!$this->isNotificationEventContext()) {
            return;
        }

        $emails = $this->getPossibleNotificationEventEmails($notificationEventType);

        /** @var EmailElement[]|TransactionalEmailElement[] $emails */
        foreach ($emails as $email) {

            /** @var TransactionalEmailEmailType $emailTypeSettings */
            $emailTypeSettings = $email->getEmailType();
            $notificationEvent = $emailTypeSettings->getNotificationEvent($email, $event);

            if (!$notificationEvent->matchNotificationEvent($event)) {
                continue;
            }

            $emailTypeSettings->setNotificationEvent($notificationEvent);
            $email->setEmailType($emailTypeSettings);

            $email->send();
        }
    }

    private function getEnabledNotificationEventTypes(): array
    {
        $enabledNotificationEmails = TransactionalEmailElement::find()
            ->select('emailTypeSettings')
            ->status(Element::STATUS_ENABLED)
            ->column();

        return array_map(static function($emailTypeSettings) {
            $settings = Json::decodeIfJson($emailTypeSettings);

            return $settings['eventId'] ?? null;
        }, $enabledNotificationEmails);
    }

    public function getPossibleNotificationEventEmails($notificationEventType): array
    {
        $currentSite = Craft::$app->getSites()->getCurrentSite();

        $enabledNotificationEmails = TransactionalEmailElement::find()
            ->status(Element::STATUS_ENABLED)
            ->siteId($currentSite->id)
            ->indexBy('id')
            ->all();

        // Return the Element IDs of the Notification Emails that are enabled using this Event
        $matchedNotificationEmails = array_filter(
            $enabledNotificationEmails,
            static function($notificationEmail) use ($notificationEventType) {
                $settings = Json::decodeIfJson($notificationEmail->emailTypeSettings);

                return isset($settings['eventId']) && $settings['eventId'] === $notificationEventType;
            });

        return $matchedNotificationEmails;
    }

    public function registerConditionRuleTypes(RegisterConditionRuleTypesEvent $event): void
    {
        if (!$elementType = $event->sender->elementType) {
            return;
        }

        if ($elementType === Entry::class) {
            // Condition that modify 'status' and 'site' won't display in Element Index by default
            $event->conditionRuleTypes[] = IsNewEntryConditionRule::class;
            $event->conditionRuleTypes[] = IsUpdatedEntryConditionRule::class;
        }

        //if ($elementType === User::class) {
        //$event->conditionRuleTypes[] = UserGroupForNewUserConditionRule::class;
        //}

        //$event->conditionRuleTypes[] = FieldChangedConditionRule::class;
    }

    private function isNotificationEventContext(): bool
    {
        if (!TransactionalModule::isEnabled()) {
            return false;
        }

        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return false;
        }

        if (Craft::$app->getUpdates()->getIsUpdatePending()) {
            return false;
        }

        return true;
    }
}
