<?php

namespace BarrelStrength\Sprout\transactional\components\emailtypes;

use BarrelStrength\Sprout\mailer\components\elements\email\conditions\EmailCondition;
use BarrelStrength\Sprout\mailer\components\elements\email\conditions\PackageConditionRule;
use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\components\mailers\SystemMailer;
use BarrelStrength\Sprout\mailer\email\EmailType;
use BarrelStrength\Sprout\mailer\MailerModule;
use BarrelStrength\Sprout\mailer\mailers\Mailer;
use BarrelStrength\Sprout\mailer\mailers\MailerSendTestInterface;
use BarrelStrength\Sprout\transactional\components\elements\fieldlayoutelements\FileAttachmentsField;
use BarrelStrength\Sprout\transactional\components\elements\fieldlayoutelements\NotificationEventField;
use BarrelStrength\Sprout\transactional\components\elements\fieldlayoutelements\SendRuleField;
use BarrelStrength\Sprout\transactional\components\elements\TransactionalEmailElement;
use BarrelStrength\Sprout\transactional\components\notificationevents\ManualNotificationEvent;
use BarrelStrength\Sprout\transactional\notificationevents\NotificationEvent;
use Craft;
use craft\fieldlayoutelements\HorizontalRule;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use Exception;

class TransactionalEmailEmailType extends EmailType
{

    public ?string $handle = 'transactional-email';

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

    public static function displayName(): string
    {
        return Craft::t('sprout-module-mailer', 'Transactional Email');
    }

    public function getMailer(): Mailer
    {
        $settings = MailerModule::getInstance()->getSettings();

        $mailer = new SystemMailer();
        $mailer->setAttributes($settings->systemMailer, false);

        return $mailer;
    }

    public static function getElementIndexType(): string
    {
        return TransactionalEmailElement::class;
    }

    public static function getTabs(FieldLayout $fieldLayout): array
    {
        $transactionalEmailPackageCondition = [
            'class' => EmailCondition::class,
            'conditionRules' => [
                new PackageConditionRule([
                    'value' => self::class,
                ]),
            ],
        ];

        $eventTab = new FieldLayoutTab();
        $eventTab->layout = $fieldLayout;
        $eventTab->name = Craft::t('sprout-module-mailer', 'Event');
        $eventTab->uid = 'SPROUT-UID-EMAIL-EVENTS-TAB';
        $eventTab->setElementCondition($transactionalEmailPackageCondition);
        $eventTab->setElements([
            new NotificationEventField(['uid' => 'SPROUT-UID-NOTIFICATION-EVENT-FIELD']),
            new HorizontalRule(),
            new FileAttachmentsField(['uid' => 'SPROUT-UID-FILE-ATTACHMENTS-FIELD']),
        ]);

        return [$eventTab];
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

    /**
     * Returns a Notification Event
     */
    public static function getNotificationEvent(EmailElement $email): NotificationEvent
    {
        $emailType = $email->getEmailTypeSettings();

        $settings = $emailType->getSettings();

        $eventId = $settings['eventId'] ?? null;

        if ($eventId !== null) {
            $notificationEvent = new $eventId();
            $eventSettings = $settings['eventSettings'][$eventId] ?? [];
            $notificationEvent->setAttributes($eventSettings, false);
        } else {
            $notificationEvent = new ManualNotificationEvent();
        }

        return $notificationEvent;
    }

    public function getMockObjectVariable(EmailElement $email): array
    {
        if ($event = self::getNotificationEvent($email)) {
            return $event->getMockEventObject();
        }

        return [];
    }
}
