<?php

namespace BarrelStrength\Sprout\sentemail\sentemail;

use BarrelStrength\Sprout\core\jobs\PurgeElementHelper;
use BarrelStrength\Sprout\core\jobs\PurgeElements;
use BarrelStrength\Sprout\sentemail\components\elements\SentEmailElement;
use BarrelStrength\Sprout\sentemail\SentEmailModule;
use Craft;
use craft\base\Component;
use craft\helpers\App;
use craft\helpers\Cp;
use craft\helpers\Json;
use craft\mail\transportadapters\BaseTransportAdapter;
use Exception;
use yii\mail\MailEvent;
use yii\mail\MessageInterface;
use yii\symfonymailer\Message;

class SentEmails extends Component
{
    public function handleLogSentEmail(MailEvent $event): void
    {
        if (!SentEmailModule::isEnabled()) {
            return;
        }

        $requestService = Craft::$app->getRequest();

        if (!$requestService->getIsCpRequest() && !$requestService->getIsSiteRequest()) {
            return;
        }

        $message = $event->message;
        $status = $event->isSuccessful
            ? SentEmailElement::STATUS_SENT
            : SentEmailElement::STATUS_FAILED;

        $this->logSentEmail($message, $status);
    }

    public function logSentEmail(MessageInterface $message, string $status): void
    {
        /** @var Message $message */
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

        if (Craft::$app->getRequest()->getIsCpRequest()) {
            $sentEmail->originSiteId = Cp::requestedSite()->id;
            $sentEmail->originSiteContext = 'cp';
        } else {
            $sentEmail->originSiteId = Craft::$app->getSites()->getCurrentSite()->id;
            $sentEmail->originSiteContext = 'site';
        }

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

        if ($status === SentEmailElement::STATUS_SENT) {
            $sentEmail->sent = true;
            $sentEmailDetails->deliveryStatus = Craft::t('sprout-module-sent-email', 'Sent');
            $sentEmailDetails->message = Craft::t('sprout-module-sent-email', 'Email sent by Craft to the service defined in the email settings.');
        } else {
            $sentEmail->sent = false;
            $sentEmailDetails->deliveryStatus = Craft::t('sprout-module-sent-email', 'Error');
            $sentEmailDetails->message = Craft::t('sprout-module-sent-email', 'Craft unable to send email. Check logs for more info.');
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
        $transportType = $emailSettings->transportType;
        $sentEmailDetails->transportType = $transportType::displayName();
        $sentEmailDetails->transportSettings = Json::encode($emailSettings->transportSettings);

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
