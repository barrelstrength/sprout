<?php

namespace BarrelStrength\Sprout\forms\controllers;

use BarrelStrength\Sprout\core\helpers\ComponentHelper;
use BarrelStrength\Sprout\forms\components\elements\FormElement;
use BarrelStrength\Sprout\forms\FormsModule;
use BarrelStrength\Sprout\forms\formtypes\FormType;
use BarrelStrength\Sprout\forms\formtypes\FormTypeHelper;
use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\emailtypes\EmailTypeHelper;
use Craft;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\web\Controller;
use yii\web\Response;

class FormTypesController extends Controller
{
    public function actionFormTypesIndexTemplate(): Response
    {
        $formTypeTypes = FormsModule::getInstance()->formTypes->getFormTypeTypes();

        $formTypes = FormTypeHelper::getFormTypes();

        return $this->renderTemplate('sprout-module-forms/_settings/form-types/index.twig', [
            'formTypes' => $formTypes,
            'formTypeTypes' => ComponentHelper::typesToInstances($formTypeTypes),
        ]);
    }

    public function actionEdit(FormType $formType = null, string $formTypeUid = null, string $type = null): Response
    {
        $this->requireAdmin();

        if ($formTypeUid) {
            $formType = FormTypeHelper::getFormTypeByUid($formTypeUid);
        }

        if (!$formType && $type) {
            $formType = new $type();
        }

        return $this->renderTemplate('sprout-module-forms/_settings/form-types/edit.twig', [
            'formType' => $formType,
        ]);
    }

    public function actionSave(): ?Response
    {
        $this->requirePostRequest();
        $this->requireAdmin();

        $formType = $this->populateFormTypeModel();

        $formTypesConfig = FormTypeHelper::getFormTypes();
        $formTypesConfig[$formType->uid] = $formType;

        if (!$formType->validate() || !FormTypeHelper::saveFormTypes($formTypesConfig)) {

            Craft::$app->session->setError(Craft::t('sprout-module-forms', 'Could not save Form Type.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'formType' => $formType,
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

        if (!FormTypeHelper::reorderFormTypes($ids)) {
            return $this->asJson([
                'success' => false,
                'error' => Craft::t('sprout-module-forms', "Couldn't reorder Form Types."),
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

        $formTypeUid = Craft::$app->request->getRequiredBodyParam('id');

        $inUse = FormElement::find()
            ->formTypeUid($formTypeUid)
            ->exists();

        if ($inUse || !FormTypeHelper::removeFormType($formTypeUid)) {
            return $this->asJson([
                'success' => false,
            ]);
        }

        return $this->asJson([
            'success' => true,
        ]);
    }

    private function populateFormTypeModel(): FormType
    {
        $type = Craft::$app->request->getRequiredBodyParam('type');
        $uid = Craft::$app->request->getRequiredBodyParam('uid');

        /** @var FormType $formType */
        $formType = new $type();
        $formType->name = Craft::$app->request->getBodyParam('name');
        $formType->uid = !empty($uid) ? $uid : StringHelper::UUID();

        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();

        $fieldLayout->type = $type;
        $formType->setFieldLayout($fieldLayout);

        $formType->formTemplateOverrideFolder = Craft::$app->request->getBodyParam('formTemplateOverrideFolder');
        $formType->enableNotificationsTab = Craft::$app->request->getBodyParam('enableNotificationsTab');
        $formType->enableReportsTab = Craft::$app->request->getBodyParam('enableReportsTab');
        $formType->enableIntegrationsTab = Craft::$app->request->getBodyParam('enableIntegrationsTab');
        $formType->enabledFormFieldTypes = Craft::$app->request->getBodyParam('enabledFormFieldTypes');

        if (!$formType::isEditable()) {
            return $formType;
        }

        $formType->formTemplate = Craft::$app->request->getBodyParam('formTemplate');


        return $formType;
    }
}
