<?php

namespace BarrelStrength\Sprout\transactional\notificationevents;

use BarrelStrength\Sprout\core\modules\SettingsRecord;
use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\transactional\components\conditions\DraftConditionRule;
use BarrelStrength\Sprout\transactional\components\conditions\FieldChangedConditionRule;
use BarrelStrength\Sprout\transactional\components\conditions\IsNewEntryConditionRule;
use BarrelStrength\Sprout\transactional\components\conditions\IsUpdatedEntryConditionRule;
use BarrelStrength\Sprout\transactional\components\conditions\RevisionConditionRule;
use BarrelStrength\Sprout\transactional\components\conditions\UserGroupForNewUserConditionRule;
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
use craft\elements\User;
use craft\events\ModelEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterConditionRuleTypesEvent;
use craft\helpers\ElementHelper;
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
    public const EVENT_REGISTER_SPROUT_NOTIFICATION_EVENT_TYPES = 'registerSproutNotificationEventTypes';

    /**
     * Returns all the available Notification Event Types
     */
    public function getNotificationEventTypes(): array
    {
        $notificationEvents[] = EntrySavedNotificationEvent::class;

        if (TransactionalModule::isPro()) {
            $notificationEvents[] = EntryDeletedNotificationEvent::class;
            $notificationEvents[] = ManualNotificationEvent::class;

            $notificationEvents[] = UserActivatedNotificationEvent::class;
            $notificationEvents[] = UserDeletedNotificationEvent::class;
            $notificationEvents[] = UserLoggedInNotificationEvent::class;
            $notificationEvents[] = UserCreatedNotificationEvent::class;
            $notificationEvents[] = UserUpdatedNotificationEvent::class;
        }

        $event = new RegisterComponentTypesEvent([
            'types' => $notificationEvents,
        ]);

        $this->trigger(self::EVENT_REGISTER_SPROUT_NOTIFICATION_EVENT_TYPES, $event);

        return $event->types;
    }

    /**
     * Returns a list of initialized Notification Events
     */
    public function getNotificationEvents(): array
    {
        $notificationEventTypes = $this->getNotificationEventTypes();

        $notificationEvents = [];

        foreach ($notificationEventTypes as $notificationEventType) {
            $event = new $notificationEventType();
            $notificationEvents[$notificationEventType] = $event;
        }

        uasort($notificationEvents, static function($a, $b): int {
            /**
             * @var $a NotificationEvent
             * @var $b NotificationEvent
             */
            return $a->displayName() <=> $b->displayName();
        });

        return $notificationEvents;
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

        foreach ($this->getActiveNotificationEventTypes() as $notificationEventType) {

            if ($notificationEventType instanceof ManualNotificationEvent) {
                continue;
            }

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

        $emails = $this->getActiveNotificationEventEmails($notificationEventType);

        /** @var EmailElement[] $emails */
        foreach ($emails as $email) {

            /** @var TransactionalEmailEmailType $emailTypeSettings */
            $emailTypeSettings = $email->getEmailTypeSettings();
            $settings = $emailTypeSettings->eventSettings[$notificationEventType] ?? [];

            /** @var NotificationEvent $notificationEvent */
            $notificationEvent = new $emailTypeSettings->eventId();
            $notificationEvent->setAttributes($settings, false);
            $notificationEvent->event = $event;

            // Set dynamic event object variable
            $emailTypeSettings->addAdditionalTemplateVariables(
                'object', $notificationEvent->getEventObject()
            );

            if (!$notificationEvent->isEnabled()) {
                continue;
            }

            if (!$notificationEvent->matchNotificationEvent($event)) {
                continue;
            }

            if (!$emailTypeSettings->sendRuleIsTrue()) {
                continue;
            }

            $email->send();
        }
    }

    public function handleActiveNotificationEventSettings(ModelEvent $event): void
    {
        /** @var EmailElement $email */
        $email = $event->sender;
        $emailType = $email->getEmailTypeSettings();

        if (ElementHelper::isDraftOrRevision($email)) {
            return;
        }

        if (!$email->getEnabledForSite()) {
            return;
        }

        if (!$emailType instanceof TransactionalEmailEmailType) {
            return;
        }

        $notificationEventType = $emailType->eventId;

        $eventClassName = $notificationEventType::getEventClassName();
        $eventName = $notificationEventType::getEventName();

        $settingsRecord = SettingsRecord::find()
            ->where([
                'siteId' => $email->siteId,
                'moduleId' => 'sprout-module-transactional',
                'name' => 'activeNotificationEvents',
            ])
            ->one();

        if ($settingsRecord === null) {
            $settingsRecord = new SettingsRecord();
            $settingsRecord->siteId = Craft::$app->getSites()->primarySite->id;
            $settingsRecord->moduleId = 'sprout-module-transactional';
            $settingsRecord->name = 'activeNotificationEvents';

            $eventSettings[$eventClassName][$eventName][$email->id] = $notificationEventType;
        } else {
            $eventSettings = Json::decode($settingsRecord->settings);
            $existingSettings = $eventSettings[$eventClassName][$eventName] ?? [];
            if (!isset($eventSettings[$eventClassName]) ||
                !in_array($email->id, $existingSettings, true)
            ) {

                $eventSettings[$eventClassName][$eventName][$email->id] = $notificationEventType;
            }
        }

        $settingsRecord->settings = $eventSettings;

        if (!$settingsRecord->save()) {
            // Unable to save Event Settings
            $event->isValid = false;
        }
    }

    private function getActiveNotificationEventTypes(): array
    {

        $activeNotificationEvents = SettingsRecord::find()
            ->select('settings')
            ->where([
                //                'siteId' => $email->siteId,
                'moduleId' => 'sprout-module-transactional',
                'name' => 'activeNotificationEvents',
            ])
            ->scalar();

        if (!$activeNotificationEvents) {
            return [];
        }

        $notificationEvents = Json::decode($activeNotificationEvents);

        $notificationEventTypes = [];

        foreach ($notificationEvents as $eventEmails) {
            foreach ($eventEmails as $emailDetails) {
                foreach ($emailDetails as $notificationEventType) {
                    $notificationEventTypes[] = $notificationEventType;
                }
            }
        }

        return $notificationEventTypes;
    }

    public function getActiveNotificationEventEmails($triggeredNotificationEventType): array
    {
        $triggeredEventClassName = $triggeredNotificationEventType::getEventClassName();
        $triggeredEventName = $triggeredNotificationEventType::getEventName();

        $activeNotificationEvents = SettingsRecord::find()
            ->select('settings')
            ->where([
                //                'siteId' => $email->siteId,
                'moduleId' => 'sprout-module-transactional',
                'name' => 'activeNotificationEvents',
            ])
            ->scalar();

        if (!$activeNotificationEvents) {
            return [];
        }

        $notificationEvents = Json::decode($activeNotificationEvents);

        $notificationEventTypeMatches = array_filter($notificationEvents, static function($notificationEvent) use ($triggeredEventClassName) {
            return $notificationEvent === $triggeredEventClassName;
        }, ARRAY_FILTER_USE_KEY);

        if (!isset($notificationEventTypeMatches[$triggeredEventClassName])) {
            return [];
        }

        $notificationEventNameMatches = array_filter($notificationEventTypeMatches[$triggeredEventClassName], static function($eventName) use ($triggeredEventName) {
            return $eventName === $triggeredEventName;
        }, ARRAY_FILTER_USE_KEY);

        if (!isset($notificationEventNameMatches[$triggeredEventName])) {
            return [];
        }

        $emailIds = array_keys($notificationEventNameMatches[$triggeredEventName]);

        // Retrieve all watched Email Elements that match the current Event
        $emails = EmailElement::find()
            ->where(['emailType' => TransactionalEmailEmailType::class])
            ->andWhere(['in', 'elements.id', $emailIds])
            ->status(Element::STATUS_ENABLED)
            ->all();

        return $emails;
    }

    public function registerConditionRuleTypes(RegisterConditionRuleTypesEvent $event): void
    {
        if (!$elementType = $event->sender->elementType) {
            return;
        }

        if ($elementType === Entry::class) {
            $event->conditionRuleTypes[] = IsNewEntryConditionRule::class;
            $event->conditionRuleTypes[] = IsUpdatedEntryConditionRule::class;

            // @todo - Is there a way to know if a generic Element supports drafts/revisions?
            $event->conditionRuleTypes[] = DraftConditionRule::class;
            $event->conditionRuleTypes[] = RevisionConditionRule::class;
        }

        if ($elementType === User::class) {
            $event->conditionRuleTypes[] = UserGroupForNewUserConditionRule::class;
        }

        $event->conditionRuleTypes[] = FieldChangedConditionRule::class;
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
