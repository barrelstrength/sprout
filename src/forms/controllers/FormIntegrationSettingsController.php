<?php

namespace BarrelStrength\Sprout\forms\controllers;

use BarrelStrength\Sprout\core\helpers\ComponentHelper;
use BarrelStrength\Sprout\forms\components\elements\FormElement;
use BarrelStrength\Sprout\forms\FormsModule;
use BarrelStrength\Sprout\forms\integrations\ElementIntegration;
use BarrelStrength\Sprout\forms\integrations\Integration;
use BarrelStrength\Sprout\forms\integrations\IntegrationRecord;
use BarrelStrength\Sprout\forms\integrations\IntegrationTypeHelper;
use Craft;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\web\Controller as BaseController;
use yii\web\Response;

class FormIntegrationSettingsController extends BaseController
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
            'uid' => $uid,
        ]);

        return $integrationType;
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
}
