<?php

namespace BarrelStrength\Sprout\mailer\controllers;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\MailerModule;
use BarrelStrength\Sprout\mailer\mailers\Mailer;
use BarrelStrength\Sprout\mailer\mailers\MailerHelper;
use Craft;
use craft\errors\ElementNotFoundException;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\web\Controller;
use Exception;
use yii\web\Response;

class MailerController extends Controller
{
    public function actionMailersIndexTemplate(): Response
    {
        $mailers = MailerHelper::getMailers();
        $mailerTypes = MailerModule::getInstance()->mailers->getMailerTypes();

        return $this->renderTemplate('sprout-module-mailer/_settings/mailers/index.twig', [
            'mailers' => $mailers,
            'mailerTypes' => $mailerTypes,
        ]);
    }

    public function actionEdit(Mailer $mailer = null, string $mailerUid = null, string $type = null): Response
    {
        $this->requireAdmin();

        if (!$mailer && $mailerUid) {
            $mailer = MailerHelper::getMailerByUid($mailerUid);
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

        $mailers = MailerHelper::getMailers();
        $mailers[$mailer->uid] = $mailer;

        if (!$mailer->validate() || !MailerHelper::saveMailers($mailers)) {

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

        if (!MailerHelper::reorderMailers($ids)) {
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

        $mailers = MailerHelper::getMailers();

        $inUse = false;
        foreach ($mailers as $mailer) {
            if ($mailer->uid === $mailerUid) {
                $inUse = true;
                break;
            }
        }

        if ($inUse || !MailerHelper::removeMailer($mailerUid)) {
            return $this->asFailure();
        }

        return $this->asSuccess();
    }

    public function actionGetSendTestHtml(): Response
    {
        $emailId = Craft::$app->getRequest()->getBodyParam('emailId');

        $email = Craft::$app->getElements()->getElementById($emailId, EmailElement::class);

        if (!$email) {
            throw new ElementNotFoundException('Email not found.');
        }

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

        if (!$mailerInstructionsSettings->validate()) {
            return $this->asJson([
                'success' => false,
                'errors' => $mailerInstructionsSettings->getErrors(),
            ]);
        }

        try {
            $mailer->send($email, $mailerInstructionsSettings);
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

        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
        $fieldLayout->type = $mailer::class;
        $mailer->setFieldLayout($fieldLayout);

        return $mailer;
    }
}
