<?php

namespace BarrelStrength\Sprout\transactional\notificationevents;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\transactional\components\conditions\DraftConditionRule;
use BarrelStrength\Sprout\transactional\components\conditions\FieldChangedConditionRule;
use BarrelStrength\Sprout\transactional\components\conditions\IsNewEntryConditionRule;
use BarrelStrength\Sprout\transactional\components\conditions\IsUpdatedEntryConditionRule;
use BarrelStrength\Sprout\transactional\components\conditions\RevisionConditionRule;
use BarrelStrength\Sprout\transactional\components\conditions\TwigExpressionConditionRule;
use BarrelStrength\Sprout\transactional\components\conditions\UserGroupForNewUserConditionRule;
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
use craft\elements\User;
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
            $emailTypeSettings = $email->getEmailTypeSettings();
            $notificationEvent = $emailTypeSettings->getNotificationEvent($email, $event);

            if (!$notificationEvent->matchNotificationEvent($event)) {
                continue;
            }

            $emailTypeSettings->setNotificationEvent($notificationEvent);
            $email->setEmailTypeSettings($emailTypeSettings);

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

                if (isset($settings['eventId']) && $settings['eventId'] === $notificationEventType) {
                    return $notificationEmail->id;
                }
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
