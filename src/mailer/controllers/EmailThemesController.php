<?php

namespace BarrelStrength\Sprout\mailer\controllers;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\emailthemes\EmailTheme;
use BarrelStrength\Sprout\mailer\emailthemes\EmailThemeHelper;
use BarrelStrength\Sprout\mailer\MailerModule;
use Craft;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\web\Controller;
use yii\web\Response;

class EmailThemesController extends Controller
{
    public function actionEmailThemesIndexTemplate(): Response
    {
        $themeTypes = MailerModule::getInstance()->emailThemes->getEmailThemeTypeInstances();

        $themes = EmailThemeHelper::getEmailThemes();

        return $this->renderTemplate('sprout-module-mailer/_settings/email-themes/index.twig', [
            'emailThemes' => $themes,
            'emailThemeTypes' => $themeTypes,
        ]);
    }

    public function actionEdit(EmailTheme $emailTheme = null, string $emailThemeUid = null, string $type = null): Response
    {
        $this->requireAdmin();

        if ($emailThemeUid) {
            $emailTheme = EmailThemeHelper::getEmailThemeByUid($emailThemeUid);
        }

        if (!$emailTheme && $type) {
            $emailTheme = new $type();
        }

        return $this->renderTemplate('sprout-module-mailer/_settings/email-themes/edit.twig', [
            'emailTheme' => $emailTheme,
        ]);
    }

    public function actionSave(): ?Response
    {
        $this->requirePostRequest();
        $this->requireAdmin();

        $emailTheme = $this->populateEmailThemeModel();

        $emailThemesConfig = EmailThemeHelper::getEmailThemes();
        $emailThemesConfig[$emailTheme->uid] = $emailTheme;

        if (!$emailTheme->validate() || !EmailThemeHelper::saveEmailThemes($emailThemesConfig)) {

            Craft::$app->session->setError(Craft::t('sprout-module-mailer', 'Could not save Email Type.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'emailTheme' => $emailTheme,
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

        if (!EmailThemeHelper::reorderEmailThemes($ids)) {
            return $this->asJson([
                'success' => false,
                'error' => Craft::t('sprout-module-mailer', "Couldn't reorder Email Themes."),
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

        $emailThemeUid = Craft::$app->request->getRequiredBodyParam('id');

        if (!EmailThemeHelper::removeEmailTheme($emailThemeUid)) {
            return $this->asJson([
                'success' => false,
            ]);
        }

        return $this->asJson([
            'success' => true,
        ]);
    }

    private function populateEmailThemeModel(): EmailTheme
    {
        $type = Craft::$app->request->getRequiredBodyParam('type');
        $uid = Craft::$app->request->getRequiredBodyParam('uid');

        /** @var EmailTheme $emailTheme */
        $emailTheme = new $type();
        $emailTheme->name = Craft::$app->request->getRequiredBodyParam('name');
        $emailTheme->uid = !empty($uid) ? $uid : StringHelper::UUID();

        if (!$emailTheme::isEditable()) {
            return $emailTheme;
        }

        $emailTheme->htmlEmailTemplate = Craft::$app->request->getBodyParam('htmlEmailTemplate');
        $emailTheme->textEmailTemplate = Craft::$app->request->getBodyParam('textEmailTemplate');
        $emailTheme->copyPasteEmailTemplate = Craft::$app->request->getBodyParam('copyPasteEmailTemplate');

        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
        $fieldLayout->type = EmailElement::class;
        $emailTheme->setFieldLayout($fieldLayout);

        return $emailTheme;
    }
}
