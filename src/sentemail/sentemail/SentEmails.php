<?php

namespace BarrelStrength\Sprout\sentemail\sentemail;

use BarrelStrength\Sprout\core\jobs\PurgeElementHelper;
use BarrelStrength\Sprout\core\jobs\PurgeElements;
use BarrelStrength\Sprout\sentemail\components\elements\SentEmailElement;
use BarrelStrength\Sprout\sentemail\SentEmailModule;
use Craft;
use craft\base\Component;
use craft\helpers\App;
use craft\mail\transportadapters\BaseTransportAdapter;
use craft\mail\transportadapters\Smtp;
use Exception;
use yii\mail\MailEvent;
use yii\mail\MessageInterface;

class SentEmails extends Component
{
    public function handleLogSentEmail(MailEvent $event): void
    {
        if (!SentEmailModule::isEnabled()) {
            return;
        }

        $message = $event->message;
        $status = $event->isSuccessful
            ? SentEmailElement::SENT
            : SentEmailElement::FAILED;

        $this->logSentEmail($message, $status);
    }

    public function logSentEmail(MessageInterface $message, string $status): void
    {
        $fromData = $message->getFrom();

        $sentEmail = new SentEmailElement();
        $sentEmail->fromName = reset($fromData);
        $sentEmail->fromEmail = array_key_first($fromData);
        $sentEmail->toEmail = array_key_first($message->getTo());

        $subject = self::getSubject($message);
        $sentEmail->title = $subject;
        $sentEmail->subjectLine = $subject;

        $sentEmail->htmlBody = $message->getHtmlBody();
        $sentEmail->textBody = $message->getTextBody();

        $sentEmailDetails = $this->_prepareDetails($sentEmail, $message, $status);
        $sentEmail->info = $sentEmailDetails->getAttributes();

        try {
            Craft::$app->getElements()->saveElement($sentEmail);
        } catch (Exception $exception) {
            Craft::error($exception->getMessage(), __METHOD__);
        }

        $this->cleanUpSentEmails();
    }

    public function cleanUpSentEmails(bool $force = false): bool
    {
        $sentEmailSettings = SentEmailModule::getInstance()->getSettings();

        $probability = $sentEmailSettings->cleanupProbability;

        // See Craft Garbage collection treatment of probability
        // https://docs.craftcms.com/v3/gc.html
        if (!$force && random_int(0, 1_000_000) >= $probability) {
            return false;
        }

        if ($sentEmailSettings->sentEmailsLimit <= 0) {
            return false;
        }

        $ids = SentEmailElement::find()
            ->offset($sentEmailSettings->sentEmailsLimit)
            ->orderBy([
                'sprout_sent_emails.dateCreated' => SORT_DESC,
            ])
            ->status(null)
            ->siteId(Craft::$app->getSites()->getCurrentSite()->id)
            ->ids();

        $purgeElements = new PurgeElements();
        $purgeElements->elementType = SentEmailElement::class;
        $purgeElements->idsToDelete = $ids;

        PurgeElementHelper::purgeElements($purgeElements);

        return true;
    }

    public function _prepareDetails(SentEmailElement $sentEmail, MessageInterface $message, string $status): SentEmailDetails
    {
        $sentEmailDetails = new SentEmailDetails();

        // GENERAL INFO
        // ------------------------------------------------------------

        if ($status === SentEmailElement::SENT) {
            $sentEmail->status = SentEmailElement::SENT;
            $sentEmailDetails->deliveryStatus = Craft::t('sprout-module-sent-email', 'Sent');
            $sentEmailDetails->message = Craft::t('sprout-module-sent-email', 'Email sent by Craft to the service defined in the email settings.');
        } else {
            $sentEmail->status = SentEmailElement::FAILED;
            $sentEmailDetails->deliveryStatus = Craft::t('sprout-module-sent-email', 'Error');
            $sentEmailDetails->message = Craft::t('sprout-module-sent-email', 'Craft unable to send email.');
        }

        // SENDER INFO
        // ------------------------------------------------------------

        $craftVersion = 'Craft ' . Craft::$app->getEditionName() . ' ' . Craft::$app->getVersion();
        $sentEmailDetails->craftVersion = $craftVersion;

        $fromData = $message->getFrom();

        $sentEmailDetails->senderName = reset($fromData);
        $sentEmailDetails->senderEmail = array_key_first($fromData);

        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            $sentEmailDetails->ipAddress = 'Console Request';
            $sentEmailDetails->userAgent = 'Console Request';
        } else {
            $sentEmailDetails->ipAddress = Craft::$app->getRequest()->getUserIP();
            $sentEmailDetails->userAgent = Craft::$app->getRequest()->getUserAgent();
        }

        // EMAIL SETTINGS
        // ------------------------------------------------------------

        $emailSettings = App::mailSettings();

        /** @var BaseTransportAdapter $transportType */
        $transportType = new $emailSettings->transportType();

        if ($emailSettings->transportSettings) {
            $transportType->setAttributes($emailSettings->transportSettings);
        }

        $sentEmailDetails->transportType = $transportType::displayName();

        if ($transportType instanceof Smtp) {
            /** @var Smtp $transportType */
            $sentEmailDetails->host = $transportType->host;
            $sentEmailDetails->port = $transportType->port;
            $sentEmailDetails->username = $transportType->username;
            $sentEmailDetails->encryptionMethod = $transportType->encryptionMethod;
            $sentEmailDetails->timeout = $transportType->timeout;
        }

        return $sentEmailDetails;
    }

    public static function getSubject(MessageInterface $message): string
    {
        // decode subject if it is encoded
        $isEncoded = preg_match('#=\?UTF-8\?B\?(.*)\?=#', $message->getSubject(), $matches);

        if ($isEncoded) {
            $encodedString = $matches[1];

            return base64_decode($encodedString);
        }

        return $message->getSubject();
    }
}
