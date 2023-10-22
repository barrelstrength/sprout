<?php

namespace BarrelStrength\Sprout\transactional\notificationevents;

use BarrelStrength\Sprout\core\components\events\ModifyRelationsTableQueryEvent;
use BarrelStrength\Sprout\core\helpers\ComponentHelper;
use BarrelStrength\Sprout\core\relations\RelationsTableInterface;
use BarrelStrength\Sprout\core\twig\TemplateHelper;
use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\components\events\OnSaveSubmissionEvent;
use BarrelStrength\Sprout\forms\components\notificationevents\SaveSubmissionNotificationEvent;
use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\emailtypes\EmailTypeHelper;
use BarrelStrength\Sprout\transactional\components\elements\TransactionalEmailElement;
use BarrelStrength\Sprout\transactional\components\emailvariants\TransactionalEmailVariant;
use BarrelStrength\Sprout\transactional\components\notificationevents\EntryCreatedNotificationEvent;
use BarrelStrength\Sprout\transactional\components\notificationevents\EntryDeletedNotificationEvent;
use BarrelStrength\Sprout\transactional\components\notificationevents\EntryUpdatedNotificationEvent;
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
use craft\events\RegisterComponentTypesEvent;
use craft\helpers\Cp;
use craft\helpers\Json;
use craft\helpers\Template;
use yii\base\Event;
use yii\db\Expression;

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

        $internalNotificationEventsTypes = [
            EntryCreatedNotificationEvent::class,
            EntryUpdatedNotificationEvent::class,
        ];

        if (TransactionalModule::isPro()) {
            $internalNotificationEventsTypes = array_merge($internalNotificationEventsTypes, [
                EntryDeletedNotificationEvent::class,
                ManualNotificationEvent::class,
                UserActivatedNotificationEvent::class,
                UserDeletedNotificationEvent::class,
                UserLoggedInNotificationEvent::class,
                UserCreatedNotificationEvent::class,
                UserUpdatedNotificationEvent::class,
            ]);
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

            /** @var TransactionalEmailVariant $emailVariantSettings */
            $emailVariantSettings = $email->getEmailVariant();
            $notificationEvent = $emailVariantSettings->getNotificationEvent($email, $event);

            if (!$notificationEvent->matchNotificationEvent($event)) {
                continue;
            }

            $emailVariantSettings->setNotificationEvent($notificationEvent);
            $email->setEmailVariant($emailVariantSettings);

            $email->send();
        }
    }

    private function getEnabledNotificationEventTypes(): array
    {
        $enabledNotificationEmails = TransactionalEmailElement::find()
            ->select('sprout_emails.emailVariantSettings')
            ->status(Element::STATUS_ENABLED)
            ->column();

        $eventTypes = array_map(static function($emailVariantSettings) {
            $settings = Json::decodeIfJson($emailVariantSettings);

            return $settings['eventId'] ?? null;
        }, $enabledNotificationEmails);

        return array_unique($eventTypes);
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
                $settings = Json::decodeIfJson($notificationEmail->emailVariantSettings);

                return isset($settings['eventId']) && $settings['eventId'] === $notificationEventType;
            });

        return $matchedNotificationEmails;
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

    public function getTransactionalRelations(NotificationEventRelationsTableInterface $element): array
    {
        $notificationEventTypes = $element->getAllowedNotificationEventRelationTypes() ?? $this->getNotificationEventTypes();

        if (Craft::$app->getDb()->getIsPgsql()) {
            $expression = new Expression('JSON_EXTRACT(sprout_emails.emailVariantSettings, "eventId")');
        } else {
            $expression = new Expression('JSON_EXTRACT(sprout_emails.emailVariantSettings, "$.eventId")');
        }

        $query = TransactionalEmailElement::find()
            ->orderBy('sprout_emails.subjectLine')
            ->where(['in', $expression, $notificationEventTypes]);

        /** @var EmailElement[] $emails */
        $emails = $query->all();

        // @todo - only supporting Save Submission event right now. Several hard coded assumptions here that need abstraction.
        $submission = new SubmissionElement();
        $submission->formId = $element->id;

        $submissionEvent = new OnSaveSubmissionEvent();
        $submissionEvent->submission = $submission;

        $relatedEmails = [];

        // At this point, we're assuming all emails have Save Submission Events
        foreach ($emails as $email) {

            /** @var TransactionalEmailVariant $emailVariantSettings */
            $emailVariantSettings = $email->getEmailVariant();
            $notificationEvent = $emailVariantSettings->getNotificationEvent($email, $submissionEvent);

            // If we have no rules, all forms will match
            if (!$rules = $notificationEvent->conditionRules['conditionRules'] ?? null) {
                $relatedEmails[] = $email;
                continue;
            }

            foreach ($rules as $key => $rule) {
                if ($rule['class'] !== 'BarrelStrength\Sprout\forms\components\elements\conditions\SubmissionFormConditionRule') {
                    unset($rules[$key]);
                }
            }

            // Just in case we have rules, but no rules are the SubmissionFormConditionRule, all forms will still match
            if (empty($rules)) {
                $relatedEmails[] = $email;
                continue;
            }

            // If we have a rule, we should now have a single FormConditionRule and can match against it
            // Assign the single rule back to the conditionRules attribute
            $notificationEvent->conditionRules['conditionRules'] = $rules;

            if (!$notificationEvent->matchNotificationEvent($submissionEvent)) {
                continue;
            }

            $relatedEmails[] = $email;
        }

        $rows = array_map(static function($element) {
            return [
                'elementId' => $element->id,
                'name' => $element->title,
                'cpEditUrl' => $element->getCpEditUrl(),
                'type' => $element->getEmailType()::displayName(),
                'actionUrl' => $element->getCpEditUrl(),
            ];
        }, $relatedEmails);

        return $rows;
    }
}
