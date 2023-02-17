<?php

namespace BarrelStrength\Sprout\mailer\controllers;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\components\elements\email\EmailPreviewInterface;
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

        $this->getPreviewEmailById($emailId, $type);
    }

    //    public function actionLivePreviewNotificationEmail(): void
    //    {
    //        $emailId = Craft::$app->getRequest()->getBodyParam('emailId');
    //
    //        $this->getPreviewEmailById($emailId);
    //    }

    public function preparePreviewEmailElement(EmailElement $email): bool
    {
        $event = TransactionalModule::getInstance()->notificationEvents->getEvent($email);

        if (!$event instanceof NotificationEvent) {

            echo Craft::t('sprout-module-mailer', 'Notification Email cannot display. The Event setting must be set.');

            Craft::$app->end();
        }

        $email->setEventObject($event->getMockEventObject());

        return true;
    }

    /**
     * Retrieves a rendered Notification Email to be shared or for Live Preview
     */
    protected function getPreviewEmailById($emailId, $type = null): void
    {
        $email = Craft::$app->getElements()->getElementById($emailId);

        if (!$email instanceof EmailElement) {
            throw new ElementNotFoundException('Email not found using id ' . $emailId);
        }

        //        $this->preparePreviewEmailElement($email);

        // The getBodyParam is for livePreviewNotification to update on change
        //        $subjectLine = Craft::$app->getRequest()->getBodyParam('subjectLine');
        //        $defaultBody = Craft::$app->getRequest()->getBodyParam('defaultBody');
        //
        //        if ($subjectLine) {
        //            $email->subjectLine = $subjectLine;
        //        }
        //
        //        if ($defaultBody) {
        //            $email->defaultBody = $defaultBody;
        //        }

        //        $fieldsLocation = Craft::$app->getRequest()->getParam('fieldsLocation', 'fields');

        //        $email->setFieldValuesFromRequest($fieldsLocation);

        $fileExtension = $type === 'text' ? 'txt' : 'html';

        $this->showPreviewEmail($email, $fileExtension);
    }

    protected function showPreviewEmail(EmailElement $email, string $fileExtension = 'html'): void
    {
        if ($fileExtension == 'txt') {
            $output = $email->getEmailTheme()->getTextBody();
        } else {
            $output = $email->getEmailTheme()->getHtmlBody();
        }

        echo $output;

        Craft::$app->end();
    }
}
