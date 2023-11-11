<?php

namespace BarrelStrength\Sprout\forms\controllers;

use BarrelStrength\Sprout\core\helpers\ComponentHelper;
use BarrelStrength\Sprout\forms\components\elements\FormElement;
use BarrelStrength\Sprout\forms\components\events\RegisterFormFeatureSettingsEvent;
use BarrelStrength\Sprout\forms\FormsModule;
use BarrelStrength\Sprout\forms\formtypes\FormType;
use BarrelStrength\Sprout\forms\formtypes\FormTypeHelper;
use BarrelStrength\Sprout\forms\integrations\IntegrationTypeHelper;
use Craft;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\web\assets\userpermissions\UserPermissionsAsset;
use craft\web\Controller;
use yii\web\Response;

class FormTypesController extends Controller
{
    public const INTERNAL_SPROUT_EVENT_REGISTER_FORM_FEATURE_SETTINGS = 'registerInternalSproutFormFeatureTabs';

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

        $integrationTypes = IntegrationTypeHelper::getIntegrationTypes();

        $integrationSettings = [];
        foreach ($integrationTypes as $uid => $integrationType) {
            $integrationSettings[$uid] = $integrationType->name;
        }
        $featureSettings['enableIntegrations'] = [
            'label' => Craft::t('sprout-module-forms', 'Enable Integrations'),
            'settings' => $integrationSettings,
        ];

        $formSettingsEvent = new RegisterFormFeatureSettingsEvent([
            'formType' => $formType,
            'featureSettings' => $featureSettings,
        ]);

        $this->trigger(self::INTERNAL_SPROUT_EVENT_REGISTER_FORM_FEATURE_SETTINGS, $formSettingsEvent);

        Craft::$app->getView()->registerAssetBundle(UserPermissionsAsset::class);

        return $this->renderTemplate('sprout-module-forms/_settings/form-types/edit.twig', [
            'formType' => $formType,
            'integrationTypes' => $integrationTypes,
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

        $integrationTypes = Craft::$app->request->getBodyParam('enabledIntegrationTypes');

        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();

        $fieldLayout->type = $type;
        $formType->setFieldLayout($fieldLayout);

        $formType->formTemplateOverrideFolder = Craft::$app->request->getBodyParam('formTemplateOverrideFolder');
        $formType->featureSettings = Craft::$app->request->getBodyParam('featureSettings');
        $formType->enabledFormFieldTypes = Craft::$app->request->getBodyParam('enabledFormFieldTypes');

        if (!$formType::isEditable()) {
            return $formType;
        }

        $formType->formTemplate = Craft::$app->request->getBodyParam('formTemplate');

        return $formType;
    }
}
