<?php

namespace BarrelStrength\Sprout\mailer\controllers;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\MailerModule;
use BarrelStrength\Sprout\mailer\mailers\Mailer;
use Craft;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\web\Controller;
use Exception;
use yii\web\Response;

class MailerController extends Controller
{
    public function actionMailersIndexTemplate(): Response
    {
        $mailers = MailerModule::getInstance()->mailers->getMailers();
        $mailerTypes = MailerModule::getInstance()->mailers->getMailerTypes();

        return $this->renderTemplate('sprout-module-mailer/_settings/mailers/index.twig', [
            'mailers' => $mailers,
            'mailerTypes' => $mailerTypes,
        ]);
    }

    public function actionEdit(Mailer $mailer = null, string $mailerUid = null, string $type = null): Response
    {
        $this->requireAdmin();

        if ($mailerUid) {
            $mailer = MailerModule::getInstance()->mailers->getMailerByUid($mailerUid);
        }

        if (!$mailer && $type) {
            $mailer = new $type();
        }

        return $this->renderTemplate('sprout-module-mailer/_settings/mailers/edit.twig', [
            'mailer' => $mailer,
        ]);
    }

    public function actionSave(): ?Response
    {
        $this->requirePostRequest();
        $this->requireAdmin();

        $mailer = $this->populateMailerModel();

        $mailers = MailerModule::getInstance()->mailers->getMailers();
        $mailers[$mailer->uid] = $mailer;

        if (!$mailer->validate() || !MailerModule::getInstance()->mailers::saveMailers($mailers)) {

            Craft::$app->session->setError(Craft::t('sprout-module-mailer', 'Could not save mailer.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'mailer' => $mailer,
            ]);

            return null;
        }

        Craft::$app->session->setNotice(Craft::t('sprout-module-mailer', 'Mailer saved.'));

        return $this->redirectToPostedUrl();
    }

    public function actionReorder(): ?Response
    {
        $this->requirePostRequest();
        $this->requireAdmin(false);

        $ids = Json::decode(Craft::$app->request->getRequiredBodyParam('ids'));

        if (!MailerModule::getInstance()->mailers::reorderMailers($ids)) {
            return $this->asJson([
                'success' => false,
                'error' => Craft::t('sprout-module-mailer', "Couldn't reorder mailers."),
            ]);
        }

        return $this->asJson([
            'success' => true,
        ]);
    }

    public function actionDelete(): ?Response
    {
        $this->requirePostRequest();
        $this->requireAdmin(false);

        $mailerUid = Craft::$app->request->getRequiredBodyParam('id');

        if (!MailerModule::getInstance()->mailers::removeMailer($mailerUid)) {
            return $this->asJson([
                'success' => false,
            ]);
        }

        return $this->asJson([
            'success' => true,
        ]);
    }

    public function actionGetSendTestHtml(): Response
    {
        $emailId = Craft::$app->getRequest()->getBodyParam('emailId');

        $email = Craft::$app->getElements()->getElementById($emailId, EmailElement::class);

        return $this->asJson([
            'success' => true,
            'html' => $email->getMailer()->getSendTestModalHtml($email),
        ]);
    }

    public function actionSendTest(): Response
    {
        $this->requireAcceptsJson();
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $emailId = $request->getRequiredBodyParam('emailId');
        $settings = $request->getRequiredBodyParam('mailerInstructionsSettings');

        /** @var EmailElement $email */
        $email = Craft::$app->getElements()->getElementById($emailId, EmailElement::class);

        $mailer = $email->getMailer();
        $mailerInstructionsTestSettings = $mailer->createMailerInstructionsTestSettingsModel();
        $mailerInstructionsTestSettings->setAttributes($settings, false);

        if (!$mailerInstructionsTestSettings->validate()) {
            return $this->asJson([
                'success' => false,
                'errors' => $mailerInstructionsTestSettings->getErrors(),
            ]);
        }

        try {
            $mailer = $email->getMailer();
            $mailer->send($email, $mailerInstructionsTestSettings);
        } catch (Exception) {
            return $this->asJson([
                'success' => false,
                'errors' => $email->getErrors(),
            ]);
        }

        return $this->asJson([
            'success' => true,
        ]);
    }

    public function actionSend(): Response
    {
        $this->requireAcceptsJson();
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $emailId = $request->getRequiredBodyParam('emailId');
        $settings = $request->getRequiredBodyParam('mailerInstructionsSettings');

        /** @var EmailElement $email */
        $email = Craft::$app->getElements()->getElementById($emailId, EmailElement::class);

        $mailer = $email->getMailer();
        $mailerInstructionsSettings = $mailer->createMailerInstructionsSettingsModel();
        $mailerInstructionsSettings->setAttributes($settings, false);

        try {
            $email->send($mailerInstructionsSettings);
        } catch (Exception) {
            return $this->asJson([
                'success' => false,
                'errors' => $email->getErrors(),
            ]);
        }

        return $this->asJson([
            'success' => true,
        ]);
    }

    private function populateMailerModel(): Mailer
    {
        $type = Craft::$app->request->getRequiredBodyParam('type');
        $uid = Craft::$app->request->getRequiredBodyParam('uid');

        /** @var Mailer $mailer */
        $mailer = new $type();
        $mailer->name = Craft::$app->request->getRequiredBodyParam('name');
        $mailer->uid = !empty($uid) ? $uid : StringHelper::UUID();

        $mailer->mailerSettings = Craft::$app->request->getBodyParam('mailerSettings');
        $mailer->setAttributes($mailer->mailerSettings, false);

        return $mailer;
    }
}
