<?php

namespace BarrelStrength\Sprout\mailer\controllers;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\components\elements\email\EmailPreviewInterface;
use BarrelStrength\Sprout\mailer\components\mailers\MailingListRecipient;
use BarrelStrength\Sprout\transactional\notificationevents\NotificationEvent;
use BarrelStrength\Sprout\transactional\TransactionalModule;
use Craft;
use craft\base\ElementInterface;
use craft\errors\ElementNotFoundException;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;
use yii\web\Response;

class PreviewController extends Controller
{
    public function actionPreview(int $emailId = null): Response
    {
        if (!$emailId) {
            throw new HttpException(404);
        }

        /** @var ElementInterface|EmailPreviewInterface $email */
        $email = Craft::$app->getElements()->getElementById($emailId);

        if (!$email) {
            throw new HttpException(404);
        }

        $currentUser = Craft::$app->getUser()->getIdentity();

        if (!$email->canView($currentUser)) {
            throw new ForbiddenHttpException('User is not authorized to perform this action.');
        }

        $template = EmailElement::EMAIL_TEMPLATE_TYPE_DYNAMIC;

        if ($email->getPreviewType() === EmailElement::EMAIL_TEMPLATE_TYPE_STATIC) {
            $template = EmailElement::EMAIL_TEMPLATE_TYPE_STATIC;
        }

        return $this->renderTemplate('sprout-module-mailer/preview/' . $template, [
            'email' => $email,
            'emailId' => $emailId,
        ]);
    }

    /**
     * Prepares an Email to be shared via token-based URL
     */
    public function actionShareEmail(int $emailId): Response
    {
        /** @var EmailPreviewInterface $email */
        $email = Craft::$app->getElements()->getElementById($emailId);

        if (!$email) {
            throw new HttpException(404);
        }

        $type = Craft::$app->getRequest()->getQueryParam('type');

        // Create the token and redirect to the entry URL with the token in place
        $token = Craft::$app->getTokens()->createToken([
            'sprout-module-mailer/preview/view-shared-email', [
                'emailId' => $emailId,
                'type' => $type,
            ],
        ]);

        $url = UrlHelper::urlWithToken($email->getPreviewUrl(), $token);

        return $this->redirect($url);
    }

    /**
     * Renders a shared Notification Email
     */
    public function actionViewSharedEmail($emailId = null, $type = null): void
    {
        $this->requireToken();

        $email = Craft::$app->getElements()->getElementById($emailId);

        if (!$email instanceof EmailElement) {
            throw new ElementNotFoundException('Email not found using id ' . $emailId);
        }

        $fileExtension = $type === 'text' ? 'txt' : 'html';

        $currentUser = Craft::$app->getUser()->getIdentity();
        $recipient = new MailingListRecipient([
            'name' => $currentUser->getName(),
            'email' => $currentUser->email,
        ]);

        $mailer = $email->getMailer();
        $mailerInstructionsTestSettings = $mailer->createMailerInstructionsTestSettingsModel();
        $additionalTemplateVariables = $mailerInstructionsTestSettings->getAdditionalTemplateVariables($email);

        $emailTheme = $email->getEmailTheme();

        $emailTheme->addTemplateVariable('email', $email);
        $emailTheme->addTemplateVariable('recipient', $recipient);
        $emailTheme->addTemplateVariables($additionalTemplateVariables);

        if ($fileExtension === 'txt') {
            $output = $emailTheme->getTextBody();
        } else {
            $output = $emailTheme->getHtmlBody();
        }

        echo $output;

        Craft::$app->end();
    }
}
