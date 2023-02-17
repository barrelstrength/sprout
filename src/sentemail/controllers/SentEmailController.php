<?php

namespace BarrelStrength\Sprout\sentemail\controllers;

use BarrelStrength\Sprout\sentemail\components\elements\SentEmailElement;
use BarrelStrength\Sprout\sentemail\SentEmailModule;
use Craft;
use craft\web\Controller;
use Exception;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class SentEmailController extends Controller
{
    /**
     * Loads the Sent Email Index page
     */
    public function actionSentEmailIndexTemplate($siteHandle = null): Response
    {
        $this->requirePermission(SentEmailModule::p('viewSentEmail'));

        if ($siteHandle === null) {
            $primarySite = Craft::$app->getSites()->getPrimarySite();
            $siteHandle = $primarySite->handle;
        }

        $currentSite = Craft::$app->getSites()->getSiteByHandle($siteHandle);

        if (!$currentSite) {
            throw new ForbiddenHttpException('Unable to find site');
        }

        $config = SentEmailModule::getInstance()->getSettings();

        return $this->renderTemplate('sprout-module-sent-email/_sentemail/index', [
            'config' => $config,
            'elementType' => SentEmailElement::class,
        ]);
    }

    /**
     * @see `sent-email/SentEmailDetailsModal.js`
     */
    public function actionGetSentEmailDetailsModalHtml(): Response
    {
        $emailId = Craft::$app->getRequest()->getBodyParam('emailId');

        $sentEmail = Craft::$app->getElements()->getElementById($emailId, SentEmailElement::class);

        $html = Craft::$app->getView()->renderTemplate('sprout-module-sent-email/_components/elements/SentEmail/sent-email-details', [
            'sentEmail' => $sentEmail,
        ]);

        return $this->asJson([
            'success' => true,
            'html' => $html,
        ]);
    }

    /**
     * @see `mailer/SendEmailModal.js` => 'get-send-email-html-action'
     */
    public function actionGetResendModalHtml(): Response
    {
        $emailId = Craft::$app->getRequest()->getBodyParam('emailId');

        $sentEmail = Craft::$app->getElements()->getElementById($emailId, SentEmailElement::class);

        $html = Craft::$app->getView()->renderTemplate('sprout-module-sent-email/_components/elements/SentEmail/resend-email-fields', [
            'sentEmail' => $sentEmail,
        ]);

        return $this->asJson([
            'success' => true,
            'html' => $html,
        ]);
    }

    /**
     * Re-sends a Sent Email
     */
    public function actionResendEmail(): bool|Response
    {
        $this->requirePostRequest();

        $emailId = Craft::$app->request->getBodyParam('emailId');

        /** @var $sentEmail SentEmailElement */
        $sentEmail = Craft::$app->elements->getElementById($emailId, SentEmailElement::class);

        $currentUser = Craft::$app->getUser()->getIdentity();

        if (!$sentEmail->canResend($currentUser)) {
            throw new ForbiddenHttpException('User not authorized to resend email.');
        }

        $recipient = Craft::$app->getRequest()->getBodyParam('recipient');

        $message = Craft::$app->getMailer()->compose()
            ->setFrom([$sentEmail->fromEmail => $sentEmail->fromName])
            ->setTo($recipient)
            ->setSubject($sentEmail->subjectLine)
            ->setHtmlBody($sentEmail->htmlBody)
            ->setTextBody($sentEmail->textBody);

        try {
            $message->send();
        } catch (Exception $exception) {
            return $this->asJson([
                'success' => false,
                'errors' => [
                    'messageFailure' => $exception->getMessage(),
                ],
            ]);
        }

        return $this->asJson([
            'success' => true,
        ]);
    }
}
