<?php

namespace BarrelStrength\Sprout\forms\controllers;

use BarrelStrength\Sprout\core\helpers\ComponentHelper;
use BarrelStrength\Sprout\forms\components\elements\FormElement;
use BarrelStrength\Sprout\forms\components\events\DefineFormFeatureSettingsEvent;
use BarrelStrength\Sprout\forms\FormsModule;
use BarrelStrength\Sprout\forms\formtypes\FormType;
use BarrelStrength\Sprout\forms\formtypes\FormTypeHelper;
use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\web\assets\userpermissions\UserPermissionsAsset;
use craft\web\Controller;
use yii\web\Response;

class FormTypesController extends Controller
{
    public const INTERNAL_SPROUT_EVENT_DEFINE_FORM_FEATURE_SETTINGS = 'defineInternalSproutFormFeatureSettings';

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

        if (!$formType) {
            if ($formTypeUid) {
                $formType = FormTypeHelper::getFormTypeByUid($formTypeUid);
            }

            if (!$formType && $type) {
                $formType = new $type();
            }
        }

        $formSettingsEvent = new DefineFormFeatureSettingsEvent([
            'formType' => $formType,
            'featureSettings' => [],
        ]);

        $this->trigger(self::INTERNAL_SPROUT_EVENT_DEFINE_FORM_FEATURE_SETTINGS, $formSettingsEvent);

        Craft::$app->getView()->registerAssetBundle(UserPermissionsAsset::class);

        return $this->renderTemplate('sprout-module-forms/_settings/form-types/edit.twig', [
            'formType' => $formType,
            'featureSettings' => $formSettingsEvent->featureSettings,
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

        $ids = Json::decode(Craft::$app->getRequest()->getRequiredBodyParam('ids'));

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

        $formTypeUid = Craft::$app->getRequest()->getRequiredBodyParam('id');

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
        $type = Craft::$app->getRequest()->getRequiredBodyParam('type');
        $uid = Craft::$app->getRequest()->getRequiredBodyParam('uid');

        /** @var FormType $formType */
        $formType = new $type();
        $formType->name = Craft::$app->getRequest()->getBodyParam('name');
        $formType->handle = Craft::$app->getRequest()->getBodyParam('handle');
        $formType->uid = !empty($uid) ? $uid : StringHelper::UUID();

        $integrationTypes = Craft::$app->getRequest()->getBodyParam('enabledIntegrationTypes');

        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();

        $fieldLayout->type = $type;
        $formType->setFieldLayout($fieldLayout);

        $formType->featureSettings = Craft::$app->getRequest()->getBodyParam('featureSettings', []);
        $formType->enabledFormFieldTypes = Craft::$app->getRequest()->getBodyParam('enabledFormFieldTypes');

        $formType->enableSaveData = Craft::$app->getRequest()->getBodyParam('enableSaveData');
        $formType->allowedAssetVolumes = Craft::$app->getRequest()->getBodyParam('allowedAssetVolumes');
        $formType->defaultUploadLocationSubpath = Craft::$app->getRequest()->getBodyParam('defaultUploadLocationSubpath');
        $formType->enableEditSubmissionViaFrontEnd = Craft::$app->getRequest()->getBodyParam('enableEditSubmissionViaFrontEnd');

        $customSettings = Craft::$app->getRequest()->getBodyParam('customSettings', []);
        $formType->setAttributes($customSettings, false);

        $formType->customTemplatesFolder = Craft::$app->getRequest()->getBodyParam('customTemplatesFolder');

        return $formType;
    }

    public function actionChangeFormTypeSlideout(): Response
    {
        $this->requireAdmin();

        $elementIds = Craft::$app->getRequest()->getQueryParam('elementIds');

        $formTypes = FormTypeHelper::getFormTypes();
        $formTypeOptions = ArrayHelper::map($formTypes, 'uid', 'name');

        return $this->asCpScreen()
            ->title(Craft::t('sprout-module-forms', 'Change Form Type'))
            ->action('sprout-module-forms/form-types/change-form-type')
            ->contentTemplate('sprout-module-forms/forms/_changeFormTypeSlideout.twig', [
                'elementIds' => implode(',', $elementIds),
                'formTypeOptions' => $formTypeOptions,
            ]);
    }

    public function actionChangeFormType(): Response
    {
        $this->requireAdmin();
        $this->requirePostRequest();

        $formTypeUid = Craft::$app->getRequest()->getRequiredBodyParam('formTypeUid');
        $elementIds = Craft::$app->getRequest()->getRequiredBodyParam('elementIds');
        $selectedElementIds = explode(',', $elementIds);

        $formType = FormTypeHelper::getFormTypeByUid($formTypeUid);

        $formElements = FormElement::find()
            ->id($selectedElementIds)
            ->where(['not', ['formTypeUid' => $formTypeUid]])
            ->all();

        $affected = 0;
        foreach ($formElements as $formElement) {
            $formElement->formTypeUid = $formTypeUid;
            Craft::$app->getElements()->saveElement($formElement);
            $affected++;
        }

        if ($affected === 0) {
            return $this->asSuccess(Craft::t('sprout-module-forms', 'Forms already use selected Form Type.'));
        }

        return $this->asSuccess(Craft::t('sprout-module-forms', 'Updated {count} Forms to use Form Type {name}', [
            'count' => $affected,
            'name' => $formType::displayName(),
        ]));
    }
}
