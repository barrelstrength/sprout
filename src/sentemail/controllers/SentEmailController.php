<?php

namespace BarrelStrength\Sprout\sentemail\controllers;

use BarrelStrength\Sprout\sentemail\components\elements\SentEmailElement;
use BarrelStrength\Sprout\sentemail\SentEmailModule;
use Craft;
use craft\helpers\Cp;
use craft\models\Site;
use craft\web\Controller;
use Exception;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class SentEmailController extends Controller
{
    /**
     * Loads the Sent Email Index page
     */
    public function actionSentEmailIndexTemplate(): Response
    {
        $this->requirePermission(SentEmailModule::p('viewSentEmail'));

        $site = Cp::requestedSite();

        if (!$site instanceof Site) {
            throw new ForbiddenHttpException('User not authorized to edit content in any sites.');
        }

        return $this->renderTemplate('sprout-module-sent-email/_sentemail/index.twig', [
            'title' => SentEmailElement::pluralDisplayName(),
            'elementType' => SentEmailElement::class,
        ]);
    }

    /**
     * @see `sent-email/SentEmailDetailsModal.js`
     */
    public function actionGetSentEmailDetailsModalHtml(): Response
    {
        $site = Cp::requestedSite();

        if (!$site instanceof Site) {
            throw new ForbiddenHttpException('User not authorized to edit content in any sites.');
        }

        $emailId = Craft::$app->getRequest()->getBodyParam('emailId');

        $sentEmail = Craft::$app->getElements()->getElementById($emailId, SentEmailElement::class, $site->id);

        $html = Craft::$app->getView()->renderTemplate('sprout-module-sent-email/_components/elements/SentEmail/sent-email-details.twig', [
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
        $site = Cp::requestedSite();

        if (!$site instanceof Site) {
            throw new ForbiddenHttpException('User not authorized to edit content in any sites.');
        }

        $emailId = Craft::$app->getRequest()->getBodyParam('emailId');

        $sentEmail = Craft::$app->getElements()->getElementById($emailId, SentEmailElement::class, $site->id);

        $html = Craft::$app->getView()->renderTemplate('sprout-module-sent-email/_components/elements/SentEmail/resend-email-fields.twig', [
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

        $site = Cp::requestedSite();

        if (!$site instanceof Site) {
            throw new ForbiddenHttpException('User not authorized to edit content in any sites.');
        }

        $emailId = Craft::$app->request->getBodyParam('emailId');

        /** @var $sentEmail SentEmailElement */
        $sentEmail = Craft::$app->elements->getElementById($emailId, SentEmailElement::class, $site->id);

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
