<?php

namespace BarrelStrength\Sprout\forms\controllers;

use BarrelStrength\Sprout\core\helpers\ComponentHelper;
use BarrelStrength\Sprout\forms\components\elements\FormElement;
use BarrelStrength\Sprout\forms\FormsModule;
use BarrelStrength\Sprout\forms\formtypes\FormType;
use BarrelStrength\Sprout\forms\formtypes\FormTypeHelper;
use BarrelStrength\Sprout\forms\integrations\ElementIntegration;
use BarrelStrength\Sprout\forms\integrations\Integration;
use BarrelStrength\Sprout\forms\integrations\IntegrationRecord;
use BarrelStrength\Sprout\forms\integrations\IntegrationTypeHelper;
use Craft;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\web\Controller as BaseController;
use yii\web\Response;

class FormIntegrationsController extends BaseController
{
    public function actionFormIntegrationsIndexTemplate(): Response
    {
        $integrationTypes = FormsModule::getInstance()->formIntegrations->getIntegrationTypeProjectConfig();
        $integrationTypeTypes = FormsModule::getInstance()->formIntegrations->getAllIntegrationTypes();

        return $this->renderTemplate('sprout-module-forms/_settings/integrations/index.twig', [
            'integrationTypes' => $integrationTypes,
            'integrationTypeTypes' => ComponentHelper::typesToInstances($integrationTypeTypes),
        ]);
    }

    public function actionEdit(Integration $integrationType = null, string $integrationTypeUid = null, string $type = null): Response
    {
        $this->requireAdmin();

        if ($integrationTypeUid) {
            $integrationType = IntegrationTypeHelper::getIntegrationTypeByUid($integrationTypeUid);
        }

        if (!$integrationType && $type) {
            $integrationType = new $type();
        }

        return $this->renderTemplate('sprout-module-forms/_settings/integrations/edit.twig', [
            'integrationType' => $integrationType,
        ]);
    }

    /**
     * Enable or disable an Integration
     */
    public function actionEnableIntegration(): Response
    {
        $this->requireAcceptsJson();

        $request = Craft::$app->getRequest();
        $integrationId = $request->getBodyParam('integrationId');
        $enabled = $request->getBodyParam('enabled');
        $enabled = $enabled == 1;

        $formId = $request->getBodyParam('formId');
        $form = FormsModule::getInstance()->forms->getFormById($formId);

        if ($integrationId == 'saveData' && $form) {
            $form->saveData = $enabled;

            if (FormsModule::getInstance()->forms->saveForm($form)) {
                return $this->asJson([
                    'success' => true,
                ]);
            }
        }

        $pieces = explode('-', $integrationId);

        if (count($pieces) == 3) {
            $integration = FormsModule::getInstance()->formIntegrations->getIntegrationById($pieces[2]);
            if ($integration !== null) {
                $integration->enabled = $enabled;
                if (FormsModule::getInstance()->formIntegrations->saveIntegration($integration)) {
                    return $this->returnJson(true, $integration);
                }
            }
        }

        return $this->asJson([
            'success' => false,
        ]);
    }

    public function actionSave(): ?Response
    {
        $this->requirePostRequest();
        $this->requireAdmin();

        $integrationType = $this->populateIntegrationTypeModel();

        $integrationTypesConfig = IntegrationTypeHelper::getIntegrationTypes();
        $integrationTypesConfig[$integrationType->uid] = $integrationType;

        if (!$integrationType->validate() || !IntegrationTypeHelper::saveIntegrationTypes($integrationTypesConfig)) {

            Craft::$app->session->setError(Craft::t('sprout-module-forms', 'Could not save Integration Type.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'integrationType' => $integrationType,
            ]);

            return null;
        }

        Craft::$app->session->setNotice(Craft::t('sprout-module-forms', 'Integration Type saved.'));

        return $this->redirectToPostedUrl();
    }

    public function actionReorder(): ?Response
    {
        $this->requirePostRequest();
        $this->requireAdmin(false);

        $ids = Json::decode(Craft::$app->request->getRequiredBodyParam('ids'));

        if (!IntegrationTypeHelper::reorderIntegrationTypes($ids)) {
            return $this->asJson([
                'success' => false,
                'error' => Craft::t('sprout-module-forms', "Couldn't reorder Integration Types."),
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

        $integrationTypeUid = Craft::$app->request->getRequiredBodyParam('id');

        /** @todo determine if any integrations are in use. */
        return $this->asJson([
            'success' => false,
        ]);

        $inUse = FormElement::find()
            ->formTypeUid($integrationTypeUid)
            ->exists();

        if ($inUse || !IntegrationTypeHelper::removeIntegrationType($integrationTypeUid)) {
            return $this->asJson([
                'success' => false,
            ]);
        }

        return $this->asJson([
            'success' => true,
        ]);
    }

    private function populateIntegrationTypeModel(): Integration
    {
        $request = Craft::$app->getRequest();

        $type = $request->getRequiredBodyParam('type');
        $uid = Craft::$app->request->getBodyParam('uid');
        $uid = empty($uid) ? StringHelper::UUID() : $uid;

        /** @var Integration $integrationType */
        $integrationType = FormsModule::getInstance()->formIntegrations->createIntegration([
            'name' => $request->getBodyParam('name'),
            'settings' => $request->getBodyParam('settings.' . $type),
            'type' => $type,
            'uid' => $uid
        ]);

        return $integrationType;
    }

    public function actionSaveIntegration(): Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $type = $request->getRequiredBodyParam('type');

        /** @var Integration $integration */
        $integration = FormsModule::getInstance()->formIntegrations->createIntegration([
            'id' => $request->getBodyParam('integrationId'),
            'formId' => $request->getBodyParam('formId'),
            'name' => $request->getBodyParam('name'),
            'enabled' => $request->getBodyParam('enabled'),
            'sendRule' => $request->getBodyParam('sendRule'),
            'type' => $type,
            'settings' => $request->getBodyParam('settings.' . $type),
        ]);

        //$integration = new $type($integration);

        if (!FormsModule::getInstance()->formIntegrations->saveIntegration($integration)) {
            Craft::error('Unable to save integration.', __METHOD__);

            return $this->returnJson(false);
        }

        Craft::info('Integration Saved', __METHOD__);

        return $this->returnJson(true, $integration);
    }

    public function actionEditIntegration(): Response
    {
        $this->requireAcceptsJson();
        $request = Craft::$app->getRequest();

        $integrationId = $request->getBodyParam('integrationId');

        $integration = FormsModule::getInstance()->formIntegrations->getIntegrationById($integrationId);

        if (!$integration instanceof Integration) {
            $message = Craft::t('sprout-module-forms', 'No integration found with id: {id}', [
                'id' => $integrationId,
            ]);

            Craft::error($message, __METHOD__);

            return $this->asJson([
                'success' => false,
                'error' => $message,
            ]);
        }

        $integration->formId = $request->getBodyParam('formId');

        return $this->asJson([
            'success' => true,
            'errors' => $integration->getErrors(),
            'integration' => [
                'id' => $integration->id,
                'name' => $integration->name,
            ],
            'template' => FormsModule::getInstance()->formIntegrations->getModalIntegrationTemplate($integration),
        ]);
    }

    public function actionDeleteIntegration(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $response = false;

        $integrationId = Craft::$app->request->getRequiredBodyParam('integrationId');
        $integration = IntegrationRecord::findOne($integrationId);

        if ($integration !== null) {
            $response = $integration->delete();
        }

        return $this->asJson([
            'success' => $response,
        ]);
    }

    /**
     * Returns an array of Form Fields to display in the Integration Modal Source Fields column
     */
    public function actionGetSourceFormFields(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        // @todo - rewrite to use UID in Formbuilder JS
        $integrationId = Craft::$app->request->getRequiredBodyParam('integrationId');
        $integration = FormsModule::getInstance()->formIntegrations->getIntegrationById($integrationId);

        if (!$integration instanceof Integration) {
            return $this->asJson([
                'success' => false,
                'sourceFormFields' => [],
            ]);
        }

        $sourceFormFields = $integration->getSourceFormFieldsAsMappingOptions();

        return $this->asJson([
            'success' => true,
            'sourceFormFields' => [],
        ]);
    }

    public function actionGetTargetIntegrationFields(): Response
    {
        return $this->asJson([
            'success' => true,
            'targetIntegrationFields' => [],
        ]);
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $integrationId = Craft::$app->request->getRequiredBodyParam('integrationId');

        /** @var ElementIntegration $integration */
        $integration = FormsModule::getInstance()->formIntegrations->getIntegrationById($integrationId);
        $integrationType = Craft::$app->getRequest()->getBodyParam('type');

        // Grab the current form values from the serialized ajax request instead of from POST
        $params = Craft::$app->getRequest()->getBodyParam('settings.' . $integrationType);

        // Ignore the current field mapping, as we're changing that
        unset($params['fieldMapping']);

        // Assign any current form values that match properties the $integration model
        foreach ($params as $key => $value) {
            if (property_exists($integration, $key)) {
                $integration->{$key} = $value;
            }
        }

        $targetIntegrationFields = $integration->getTargetIntegrationFieldsAsMappingOptions();

        return $this->asJson([
            'success' => true,
            'targetIntegrationFields' => $targetIntegrationFields,
        ]);
    }

    private function returnJson(bool $success, Integration $integration): Response
    {
        // @todo how we should return errors to the edit integration modal? template response is disabled for now
        return $this->asJson([
            'success' => $success,
            'errors' => $integration->getErrors(),
            'integration' => [
                'id' => $integration->id,
                'name' => $integration->name,
                'enabled' => $integration->enabled,
            ],
            //'template' => $success ? false : Sprout::$app->integrations->getModalIntegrationTemplate($integration),
        ]);
    }
}
