<?php

namespace BarrelStrength\Sprout\mailer\controllers;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\components\emailthemes\CustomEmailTheme;
use BarrelStrength\Sprout\mailer\emailthemes\EmailTheme;
use BarrelStrength\Sprout\mailer\emailthemes\EmailThemeRecord;
use BarrelStrength\Sprout\mailer\MailerModule;
use Craft;
use craft\helpers\StringHelper;
use craft\web\Controller;
use yii\web\Response;

class EmailThemesController extends Controller
{
    public function actionEntryTypesIndexTemplate(): Response
    {
        $themes = MailerModule::getInstance()->emailThemes->getEmailThemes();

        return $this->renderTemplate('sprout-module-mailer/_settings/email-themes/index', [
            'emailThemes' => $themes,
        ]);
    }

    public function actionEdit(EmailTheme $emailTheme = null, int $emailThemeId = null): Response
    {
        $this->requireAdmin();

        if (!$emailTheme) {
            $emailTheme = $this->getEmailThemeModel($emailThemeId);
        }

        return $this->renderTemplate('sprout-module-mailer/_settings/email-themes/edit', [
            'emailTheme' => $emailTheme,
        ]);
    }

    public function actionSave(): ?Response
    {
        $this->requirePostRequest();
        $this->requireAdmin();

        $emailTheme = $this->populateEmailThemeModel();

        $settingsKey = $emailTheme->uid;
        $configPath = MailerModule::projectConfigPath('emailThemes.' . $settingsKey);

        if (!$emailTheme->validate() || !Craft::$app->getProjectConfig()->set($configPath, $emailTheme->getConfig(), "Update Sprout Settings for “{$configPath}”")) {
            Craft::$app->session->setError(Craft::t('sprout-module-mailer', 'Could not save Email Type.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'emailTheme' => $emailTheme,
            ]);

            return null;
        }

        Craft::$app->session->setNotice(Craft::t('sprout-module-mailer', 'Email Type saved.'));

        return $this->redirectToPostedUrl();
    }

    private function getFieldLayoutSettings(): ?array
    {
        if (!Craft::$app->getRequest()->getBodyParam('fieldLayout')) {
            return [];
        }

        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();

        if (!$fieldLayout) {
            return [];
        }

        return [
            $fieldLayout->uid => $fieldLayout->getConfig(),
        ];
    }

    private function getEmailThemeModel(int $emailThemeId = null): EmailTheme
    {
        $emailThemeRecord = EmailThemeRecord::find()
            ->where([
                'id' => $emailThemeId,
            ])
            ->one();

        if ($emailThemeRecord === null) {
            return new CustomEmailTheme();
        }

        $emailTheme = new $emailThemeRecord->type();
        $emailTheme->id = $emailThemeRecord->id;
        $emailTheme->fieldLayoutId = $emailThemeRecord->fieldLayoutId;
        $emailTheme->name = $emailThemeRecord->name;
        $emailTheme->htmlEmailTemplatePath = $emailThemeRecord->htmlEmailTemplatePath;
        $emailTheme->copyPasteEmailTemplatePath = $emailThemeRecord->copyPasteEmailTemplatePath;

        return $emailTheme;
    }

    private function populateEmailThemeModel(): EmailTheme
    {
        $emailTheme = new CustomEmailTheme();

        $emailTheme->id = Craft::$app->request->getBodyParam('emailThemeId');
        $emailTheme->name = Craft::$app->request->getBodyParam('name');
        $emailTheme->fieldLayoutId = Craft::$app->request->getBodyParam('fieldLayoutId');
        $emailTheme->htmlEmailTemplatePath = Craft::$app->request->getBodyParam('htmlEmailTemplatePath');
        $emailTheme->copyPasteEmailTemplatePath = Craft::$app->request->getBodyParam('copyPasteEmailTemplatePath');

        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
        $fieldLayout->type = EmailElement::class;
        $emailTheme->setFieldLayout($fieldLayout);

        $isNew = !$emailTheme->id;

        if ($isNew) {
            $emailTheme->uid = StringHelper::UUID();
        } else {
            $emailThemeRecord = EmailThemeRecord::find()
                ->where([
                    'id' => $emailTheme->id,
                ])
                ->one();

            $emailTheme->uid = $emailThemeRecord->uid;
        }

        return $emailTheme;
    }
}
