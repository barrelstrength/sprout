<?php

namespace BarrelStrength\Sprout\forms\controllers;

use BarrelStrength\Sprout\forms\FormsModule;
use BarrelStrength\Sprout\forms\formtemplates\FormTemplateSet;
use BarrelStrength\Sprout\forms\formtemplates\FormThemeHelper;
use Craft;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\web\Controller;
use yii\web\Response;

class FormThemesController extends Controller
{
    public function actionFormThemesIndexTemplate(): Response
    {
        $themeTypes = FormsModule::getInstance()->formTemplates->getFormTemplateTypesInstances();

        $themes = FormThemeHelper::getFormThemes();

        return $this->renderTemplate('sprout-module-forms/_settings/form-themes/index.twig', [
            'formThemes' => $themes,
            'formThemeTypes' => $themeTypes,
        ]);
    }

    public function actionEdit(FormTemplateSet $formTemplateSet = null, string $formThemeUid = null, string $handle = null): Response
    {
        $this->requireAdmin();

        if (!$formTemplateSet && $handle) {
            $formTemplateSet = FormThemeHelper::getFormThemeByHandle($handle);
        }
        if (!$formTemplateSet) {
            $formTemplateSet = FormThemeHelper::getFormThemeByUid($formThemeUid);
        }

        return $this->renderTemplate('sprout-module-forms/_settings/form-themes/edit.twig', [
            'formTheme' => $formTemplateSet,
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

    private function populateFormThemeModel(): FormTemplateSet
    {
        $type = Craft::$app->request->getRequiredBodyParam('type');
        $uid = Craft::$app->request->getRequiredBodyParam('uid');

        /** @var FormTemplateSet $formTheme */
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
