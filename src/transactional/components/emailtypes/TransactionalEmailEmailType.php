<?php

namespace BarrelStrength\Sprout\transactional\components\emailtypes;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\emailtypes\EmailType;
use BarrelStrength\Sprout\mailer\mailers\Mailer;
use BarrelStrength\Sprout\mailer\mailers\MailerHelper;
use BarrelStrength\Sprout\mailer\mailers\MailerSendTestInterface;
use BarrelStrength\Sprout\transactional\components\elements\fieldlayoutelements\FileAttachmentsField;
use BarrelStrength\Sprout\transactional\components\elements\fieldlayoutelements\NotificationEventField;
use BarrelStrength\Sprout\transactional\components\elements\TransactionalEmailElement;
use BarrelStrength\Sprout\transactional\components\notificationevents\ManualNotificationEvent;
use BarrelStrength\Sprout\transactional\notificationevents\NotificationEvent;
use Craft;
use craft\fieldlayoutelements\HorizontalRule;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use yii\base\Event;

class TransactionalEmailEmailType extends EmailType
{
    /**
     * The qualified namespace of the Email Notification Event
     */
    public ?string $eventId = null;

    /**
     * Enable or disable file attachments when notification emails are sent.
     *
     * If disabled, files will still be stored in Craft after form submission.
     * This only determines if they should also be attached and sent via email.
     */
    public bool $enableFileAttachments = false;

    /**
     * Statement that gets evaluated to true/false to determine this event will be fired
     *
     * No value is considered 'Always Send'
     */
    public ?string $sendRule = null;

    /**
     * Any options that have been set for your Event. Stored as JSON.
     */
    public ?array $eventSettings = [];

    private ?NotificationEvent $_notificationEvent = null;

    public static function displayName(): string
    {
        return Craft::t('sprout-module-mailer', 'Transactional Email');
    }

    public static function refHandle(): ?string
    {
        return 'transactional-email';
    }

    public function getMailer(EmailElement $email): ?Mailer
    {
        return MailerHelper::getMailerByUid($email->mailerUid);
    }

    public static function elementType(): string
    {
        return TransactionalEmailElement::class;
    }

    public static function getFieldLayoutTab(FieldLayout $fieldLayout): FieldLayoutTab
    {
        $eventTab = new FieldLayoutTab();
        $eventTab->layout = $fieldLayout;
        $eventTab->name = Craft::t('sprout-module-mailer', 'Event');
        $eventTab->uid = 'SPROUT-UID-EMAIL-TYPE-TAB';
        $eventTab->setElements([
            new NotificationEventField([
                'uid' => 'SPROUT-UID-EMAIL-NOTIFICATION-EVENT-FIELD',
            ]),
            new HorizontalRule([
                'uid' => 'SPROUT-UID-EMAIL-HORIZONTAL-RULE-EMAIL-TYPE-TAB-1',
            ]),
            new FileAttachmentsField([
                'uid' => 'SPROUT-UID-EMAIL-FIELD-FILE-ATTACHMENT',
            ]),
        ]);

        return $eventTab;
    }

    public static function getAdditionalButtonsHtml(EmailElement $email): string
    {
        $mailer = $email->getMailer();

        if (!$mailer instanceof MailerSendTestInterface) {
            return '';
        }

        return Craft::$app->getView()->renderTemplate('sprout-module-transactional/_components/emailtypes/sendTestButton.twig', [
            'email' => $email,
        ]);
    }

    public function setNotificationEvent($notificationEvent): void
    {
        $this->_notificationEvent = $notificationEvent;
    }

    /**
     * Returns a Notification Event
     */
    public function getNotificationEvent(EmailElement $email, Event $event = null): NotificationEvent
    {
        if ($this->_notificationEvent !== null) {
            return $this->_notificationEvent;
        }

        $emailType = $email->getEmailType();

        $settings = $emailType->getSettings();

        $eventId = $settings['eventId'] ?? null;

        if ($eventId !== null) {
            $notificationEvent = new $eventId([
                'event' => $event,
            ]);
            $eventSettings = $settings['eventSettings'][$eventId] ?? [];
            $notificationEvent->setAttributes($eventSettings, false);
        } else {
            $notificationEvent = new ManualNotificationEvent();
        }

        return $notificationEvent;
    }
}
