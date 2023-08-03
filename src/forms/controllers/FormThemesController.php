<?php

namespace BarrelStrength\Sprout\forms\controllers;

use BarrelStrength\Sprout\core\helpers\ComponentHelper;
use BarrelStrength\Sprout\forms\FormsModule;
use BarrelStrength\Sprout\forms\formthemes\FormTheme;
use BarrelStrength\Sprout\forms\formthemes\FormThemeHelper;
use BarrelStrength\Sprout\mailer\emailthemes\EmailThemeHelper;
use Craft;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\web\Controller;
use yii\web\Response;

class FormThemesController extends Controller
{
    public function actionFormThemesIndexTemplate(): Response
    {
        $themeTypes = FormsModule::getInstance()->formThemes->getFormThemeTypes();

        $themes = FormThemeHelper::getFormThemes();

        return $this->renderTemplate('sprout-module-forms/_settings/form-themes/index.twig', [
            'formThemes' => $themes,
            'formThemeTypes' => ComponentHelper::typesToInstances($themeTypes),
        ]);
    }

    public function actionEdit(FormTheme $formTheme = null, string $formThemeUid = null, string $type = null): Response
    {
        $this->requireAdmin();

        if ($formThemeUid) {
            $formTheme = FormThemeHelper::getFormThemeByUid($formThemeUid);
        }

        if (!$formTheme && $type) {
            $formTheme = new $type();
        }

        return $this->renderTemplate('sprout-module-forms/_settings/form-themes/edit.twig', [
            'formTheme' => $formTheme,
        ]);
    }

    public function actionSave(): ?Response
    {
        $this->requirePostRequest();
        $this->requireAdmin();

        $formTheme = $this->populateFormThemeModel();

        if (!$formTheme->uid) {
            $formTheme->uid = StringHelper::UUID();
        }

        $formThemesConfig = FormThemeHelper::getFormThemes();
        $formThemesConfig[$formTheme->uid] = $formTheme;

        if (!$formTheme->validate() || !FormThemeHelper::saveFormThemes($formThemesConfig)) {

            Craft::$app->session->setError(Craft::t('sprout-module-forms', 'Could not save Form Type.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'formTheme' => $formTheme,
            ]);

            return null;
        }

        Craft::$app->session->setNotice(Craft::t('sprout-module-forms', 'Form Type saved.'));

        return $this->redirectToPostedUrl();
    }

    public function actionReorder(): ?Response
    {
        $this->requirePostRequest();
        $this->requireAdmin(false);

        $ids = Json::decode(Craft::$app->request->getRequiredBodyParam('ids'));

        if (!FormThemeHelper::reorderFormThemes($ids)) {
            return $this->asJson([
                'success' => false,
                'error' => Craft::t('sprout-module-forms', "Couldn't reorder Form Themes."),
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

        $formThemeUid = Craft::$app->request->getRequiredBodyParam('id');

        if (!FormThemeHelper::removeFormTheme($formThemeUid)) {
            return $this->asJson([
                'success' => false,
            ]);
        }

        return $this->asJson([
            'success' => true,
        ]);
    }

    private function populateFormThemeModel(): FormTheme
    {
        $type = Craft::$app->request->getRequiredBodyParam('type');
        $uid = Craft::$app->request->getRequiredBodyParam('uid');

        /** @var FormTheme $formTheme */
        $formTheme = new $type();
        $formTheme->name = Craft::$app->request->getBodyParam('name');
        $formTheme->uid = $uid ?? StringHelper::UUID();

        if (!$formTheme::isEditable()) {
            return $formTheme;
        }

        $formTheme->formTemplate = Craft::$app->request->getBodyParam('formTemplate');

        return $formTheme;
    }
}
