<?php

namespace BarrelStrength\Sprout\transactional\components\emailvariants;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\components\mailers\SystemMailer;
use BarrelStrength\Sprout\mailer\emailvariants\EmailVariant;
use BarrelStrength\Sprout\mailer\mailers\Mailer;
use BarrelStrength\Sprout\mailer\mailers\MailerHelper;
use BarrelStrength\Sprout\mailer\mailers\MailerSendTestInterface;
use BarrelStrength\Sprout\transactional\components\elements\fieldlayoutelements\FileAttachmentsField;
use BarrelStrength\Sprout\transactional\components\elements\fieldlayoutelements\NotificationEventField;
use BarrelStrength\Sprout\transactional\components\elements\TransactionalEmailElement;
use BarrelStrength\Sprout\transactional\components\mailers\TransactionalMailer;
use BarrelStrength\Sprout\transactional\components\notificationevents\ManualNotificationEvent;
use BarrelStrength\Sprout\transactional\notificationevents\NotificationEvent;
use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\HorizontalRule;
use craft\helpers\App;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use InvalidArgumentException;
use yii\base\Event;

class TransactionalEmailVariant extends EmailVariant
{
    /**
     * The qualified namespace of the Email Notification Event
     */
    public ?string $eventId = null;

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
        $emailType = $email->getEmailType();

        if ($emailType->mailerUid === MailerHelper::CRAFT_MAILER_SETTINGS) {
            return self::getDefaultMailer();
        }

        return MailerHelper::getMailerByUid($emailType->mailerUid);
    }

    public static function getDefaultMailer(): Mailer
    {
        $mailSettings = App::mailSettings();

        $mailer = new TransactionalMailer([
            'name' => Craft::t('sprout-module-transactional', 'Craft Email Settings'),
            'senderEditBehavior' => SystemMailer::SENDER_BEHAVIOR_CRAFT,
            'approvedSenders' => [
                [
                    'fromName' => $mailSettings->fromName,
                    'fromEmail' => $mailSettings->fromEmail,
                ],
            ],
            'approvedReplyToEmails' => [
                [
                    'replyToEmail' => $mailSettings->replyToEmail,
                ],
            ],
            'uid' => 'SPROUT-UID-EMAIL-DEFAULT-MAILER',
        ]);

        return $mailer;
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

        return Craft::$app->getView()->renderTemplate('sprout-module-transactional/_components/emailvariants/sendTestButton.twig', [
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
    public function getNotificationEvent(ElementInterface $email, Event $event = null): NotificationEvent
    {
        if (!$email instanceof EmailElement) {
            throw new InvalidArgumentException('Element must be an instance of ' . EmailElement::class);
        }

        if ($this->_notificationEvent !== null) {
            return $this->_notificationEvent;
        }

        $emailVariant = $email->getEmailVariant();

        $settings = $emailVariant->getSettings();

        $eventId = $settings['eventId'] ?? null;

        if ($eventId !== null) {
            $notificationEvent = new $eventId([
                'event' => $event,
            ]);
            $eventSettings = $settings['eventSettings'] ?? [];
            $notificationEvent->setAttributes($eventSettings, false);
        } else {
            $notificationEvent = new ManualNotificationEvent();
        }

        return $notificationEvent;
    }

    public function prepareEmailVariantSettingsForDb(array $settings): array
    {
        $settings['eventId'] = $this->eventId;
        $settings['eventSettings'] = $this->eventSettings;

        if (isset($settings['eventId'])) {
            $eventSettings = $settings['eventSettings'][$settings['eventId']] ?? null;
            $settings['eventSettings'] = $eventSettings;
        }

        return $settings;
    }
}
