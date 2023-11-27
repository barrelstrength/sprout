<?php

namespace BarrelStrength\Sprout\forms\controllers;

use BarrelStrength\Sprout\forms\components\elements\conditions\SubmissionCondition;
use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\FormsModule;
use BarrelStrength\Sprout\forms\integrations\Integration;
use BarrelStrength\Sprout\forms\integrations\IntegrationRecord;
use BarrelStrength\Sprout\forms\integrations\IntegrationTypeHelper;
use Craft;
use craft\helpers\Cp;
use craft\helpers\Template;
use craft\web\assets\conditionbuilder\ConditionBuilderAsset;
use craft\web\Controller as BaseController;
use yii\web\Response;

class FormIntegrationsController extends BaseController
{
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

            return $this->asJson([
                'success' => false,
                'integration' => $integration,
            ]);
        }

        Craft::info('Integration Saved', __METHOD__);

        return $this->asJson([
            'success' => true,
            'integration' => $integration,
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

    public function actionEditIntegrationSlideout(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $this->requirePermission(FormsModule::p('editIntegrations'));

        $integrationTypeUid = Craft::$app->getRequest()->getRequiredParam('integrationTypeUid');
        $integrationUid = Craft::$app->getRequest()->getRequiredParam('integrationUid');
        $formId = Craft::$app->getRequest()->getRequiredParam('formId');
        $integrationsFormFieldMetadata = Craft::$app->getRequest()->getRequiredParam('integrationsFormFieldMetadata');

        $integration = null;

        if ($integrationUid) {
            $integration = FormsModule::getInstance()->formIntegrations->getIntegrationByUid($integrationUid);
        }

        if (!$integration) {
            $integrationType = IntegrationTypeHelper::getIntegrationTypeByUid($integrationTypeUid);
            $integration = new $integrationType([
                'formId' => $formId, // initiate with formId to ensure mapping gets refreshed
                'sourceFormFieldsFromPage' => $integrationsFormFieldMetadata,
            ]);
        }

        // Ensure formId is set for existing integrations
        $integration->formId = $formId;

        $view = Craft::$app->getView();
        $view->startJsBuffer();
        $conditionBuilderHtml = $this->getIntegrationSendRuleConditionBuilder($integration);
        $html = Craft::$app->getView()->renderTemplate('sprout-module-forms/forms/_editIntegration', [
            'integration' => $integration,
            'conditionBuilderHtml' => Template::raw($conditionBuilderHtml),
        ]);
        $slideoutJs = $view->clearJsBuffer();

        return $this->asJson([
            'success' => true,
            'integration' => $integration,
            'html' => $html,
            'slideoutJs' => $slideoutJs,
        ]);
    }

    protected function getIntegrationSendRuleConditionBuilder(Integration $integration): string
    {
        Craft::$app->getView()->registerAssetBundle(ConditionBuilderAsset::class);

        /** @var SubmissionCondition $condition */
        $condition = !empty($integration->conditionRules)
            ? Craft::$app->conditions->createCondition($integration->conditionRules)
            : Craft::createObject(SubmissionCondition::class);
        $condition->elementType = SubmissionElement::class;
        $condition->sortable = true;
        $condition->mainTag = 'div';
        $condition->name = 'conditionRules';
        $condition->id = 'conditionRules';

        $condition->queryParams[] = 'formId';

        return Cp::fieldHtml($condition->getBuilderHtml(), [
            'label' => Craft::t('sprout-module-forms', 'Send Rules'),
            'instructions' => Craft::t('sprout-module-forms', 'Only send an email for events that match the following rules:'),
        ]);
    }
}
