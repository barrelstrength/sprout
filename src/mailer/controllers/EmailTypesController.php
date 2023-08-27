<?php

namespace BarrelStrength\Sprout\mailer\controllers;

use BarrelStrength\Sprout\core\helpers\ComponentHelper;
use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\emailtypes\EmailType;
use BarrelStrength\Sprout\mailer\emailtypes\EmailTypeHelper;
use BarrelStrength\Sprout\mailer\MailerModule;
use BarrelStrength\Sprout\mailer\mailers\MailerHelper;
use Craft;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\web\Controller;
use yii\web\Response;

class EmailTypesController extends Controller
{
    public function actionEmailTypesIndexTemplate(): Response
    {
        $emailTypeTypes = MailerModule::getInstance()->emailTypes->getEmailTypeTypes();

        $emailTypes = EmailTypeHelper::getEmailTypes();

        return $this->renderTemplate('sprout-module-mailer/_settings/email-types/index.twig', [
            'emailTypes' => $emailTypes,
            'emailTypeTypes' => ComponentHelper::typesToInstances($emailTypeTypes),
        ]);
    }

    public function actionEdit(EmailType $emailType = null, string $emailTypeUid = null, string $type = null): Response
    {
        $this->requireAdmin();

        if ($emailTypeUid) {
            $emailType = EmailTypeHelper::getEmailTypeByUid($emailTypeUid);
        }

        if (!$emailType && $type) {
            $emailType = new $type();
        }

        $mailers = MailerHelper::getMailers();

        $mailerTypeOptions[] = [
            'label' => Craft::t('sprout-module-mailer', 'Craft Mailer Settings'),
            'value' => MailerHelper::CRAFT_MAILER_SETTINGS,
        ];

        foreach ($mailers as $mailer) {
            $mailerTypeOptions[] = [
                'label' => $mailer->name,
                'value' => $mailer->uid,
            ];
        }

        return $this->renderTemplate('sprout-module-mailer/_settings/email-types/edit.twig', [
            'emailType' => $emailType,
            'mailerTypeOptions' => $mailerTypeOptions ?? [],
        ]);
    }

    public function actionSave(): ?Response
    {
        $this->requirePostRequest();
        $this->requireAdmin();

        $emailType = $this->populateEmailTypeModel();

        $emailTypesConfig = EmailTypeHelper::getEmailTypes();
        $emailTypesConfig[$emailType->uid] = $emailType;

        if (!$emailType->validate() || !EmailTypeHelper::saveEmailTypes($emailTypesConfig)) {

            Craft::$app->session->setError(Craft::t('sprout-module-mailer', 'Could not save Email Variant.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'emailType' => $emailType,
            ]);

            return null;
        }

        Craft::$app->session->setNotice(Craft::t('sprout-module-mailer', 'Email Type saved.'));

        return $this->redirectToPostedUrl();
    }

    public function actionReorder(): ?Response
    {
        $this->requirePostRequest();
        $this->requireAdmin(false);

        $ids = Json::decode(Craft::$app->request->getRequiredBodyParam('ids'));

        if (!EmailTypeHelper::reorderEmailTypes($ids)) {
            return $this->asJson([
                'success' => false,
                'error' => Craft::t('sprout-module-mailer', "Couldn't reorder Email Types."),
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

        $emailTypeUid = Craft::$app->request->getRequiredBodyParam('id');

        $inUse = EmailElement::find()
            ->emailTypeUid($emailTypeUid)
            ->exists();

        if ($inUse || !EmailTypeHelper::removeEmailType($emailTypeUid)) {
            return $this->asFailure();
        }

        return $this->asSuccess();
    }

    private function populateEmailTypeModel(): EmailType
    {
        $type = Craft::$app->request->getRequiredBodyParam('type');
        $uid = Craft::$app->request->getRequiredBodyParam('uid');

        /** @var EmailType $emailType */
        $emailType = new $type();
        $emailType->name = Craft::$app->request->getRequiredBodyParam('name');
        $emailType->mailerUid = Craft::$app->request->getRequiredBodyParam('mailerUid');
        $emailType->uid = !empty($uid) ? $uid : StringHelper::UUID();

        // Allow UI Elements to be added to the Field Layout
        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
        $fieldLayout->type = $type;
        $emailType->setFieldLayout($fieldLayout);

        if (!$emailType::isEditable()) {
            return $emailType;
        }

        $emailType->displayPreheaderText = Craft::$app->request->getBodyParam('displayPreheaderText');
        $emailType->htmlEmailTemplate = Craft::$app->request->getBodyParam('htmlEmailTemplate');
        $emailType->textEmailTemplate = Craft::$app->request->getBodyParam('textEmailTemplate');
        $emailType->copyPasteEmailTemplate = Craft::$app->request->getBodyParam('copyPasteEmailTemplate');

        return $emailType;
    }
}
