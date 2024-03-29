<?php

namespace BarrelStrength\Sprout\transactional\notificationevents;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\transactional\TransactionalModule;
use craft\base\SavableComponent;
use yii\base\Event;

abstract class NotificationEvent extends SavableComponent
{
    public EmailElement $email;

    public ?Event $event = null;

    /**
     * Enable or disable file attachments when notification emails are sent.
     *
     * If disabled, files will still be stored in Craft after form submission. This only determines if they should also be sent via email.
     */
    public bool $enableFileAttachments = false;

    public function __construct($config = [])
    {
        if (isset($config['event'])) {
            $this->event = $config['event'];
        }

        parent::__construct($config);
    }

    public function __toString()
    {
        return static::displayName();
    }

    /**
     * Returns the namespace as a string with dashes so it can be used in html as a css class
     */
    final public function getEventId(): ?string
    {
        return strtolower(str_replace('\\', '-', $this::class));
    }

    /**
     * Returns the fully qualified class name to which the event handler needs to attach.
     *
     * This value is used for the Event::on $class parameter
     *
     * @example Event::on($class, $name, function($handler) { ... });
     *
     * @see Event
     */
    abstract public static function getEventClassName(): ?string;

    /**
     * Returns the event name.
     *
     * This value is used for the Event::on $name parameter
     *
     * @example Event::on($class, $name, function($handler) { ... });
     *
     * @see Event
     */
    abstract public static function getEventName(): ?string;

    /**
     * Returns a short description of this event
     *
     * @example Triggers when an entry is saved
     */
    public function getDescription(): string
    {
        return '';
    }

    /**
     * A tip that displays below the event when selected to help users
     * understand what the event does and how to use it in email templates.
     */
    public function getTipHtml(): ?string
    {
        return '';
    }

    /**
     * Returns the object that represents the event. The object returned will be passed to renderObjectTemplate
     * and be available to output in the Notification Email templates via Twig Expressions:
     *
     * @example   - Usage in Notification Email Templates
     *            If getEventVariables returns a craft\elements\Entry model as 'entry', the Notification
     *            Email Templates can output data from that model such as {entry.title} OR {{ object.entry.title }}
     */
    public function getEventVariables(): array
    {
        return [];
    }

    /**
     * Returns mock data for $event->params that will be used when sending test Notification Emails.
     *
     * Real data can be dynamically retrieved from your database or a static fallback can be provided.
     */
    public function getMockEventVariables(): array
    {
        return [];
    }

    /**
     * Gives Notification Event a chance to check an event against
     * other settings to confirm if it can be sent
     */
    public function matchNotificationEvent(Event $event): bool
    {
        return true;
    }

    public function isEnabled(): bool
    {
        $notificationEventTypes = TransactionalModule::getInstance()->notificationEvents->getNotificationEventTypes();

        if (!in_array(static::class, $notificationEventTypes, true)) {
            return false;
        }

        return true;
    }
}
