<?php

namespace BarrelStrength\Sprout\mailer\controllers;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\components\elements\email\EmailPreviewInterface;
use BarrelStrength\Sprout\mailer\components\mailers\MailingListRecipient;
use BarrelStrength\Sprout\mailer\components\mailers\SystemMailerInstructionsSettingsTestSettings;
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

        $currentUser = Craft::$app->getUser()->getIdentity();
        $recipient = new MailingListRecipient([
            'name' => $currentUser->getName(),
            'email' => $currentUser->email,
        ]);

        // @todo - Can we abstract how we call SystemMailer::_buildMessage() so we can do the same here?
        $mailer = $email->getMailer();
        $mailerInstructionsTestSettings = $mailer->createMailerInstructionsTestSettingsModel();

        $emailType = $email->getEmailType();

        $emailType->addTemplateVariable('recipient', $recipient);

        // @todo - assumes specific mailer instructions settings, should this be abstracted?
        if ($mailerInstructionsTestSettings instanceof SystemMailerInstructionsSettingsTestSettings) {
            $additionalTemplateVariables = $mailerInstructionsTestSettings->getAdditionalTemplateVariables($email);
            $emailType->addTemplateVariables($additionalTemplateVariables);
        }

        $emailType->addTemplateVariable('email', $email);

        $fileExtension = $type === 'text' ? 'txt' : 'html';

        if ($fileExtension === 'txt') {
            $output = $emailType->getTextBody();
        } else {
            $output = $emailType->getHtmlBody();
        }

        echo $output;

        Craft::$app->end();
    }
}
