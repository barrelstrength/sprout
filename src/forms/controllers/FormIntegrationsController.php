<?php

namespace BarrelStrength\Sprout\forms\controllers;

use BarrelStrength\Sprout\forms\components\elements\conditions\SubmissionCondition;
use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\FormsModule;
use BarrelStrength\Sprout\forms\integrations\Integration;
use BarrelStrength\Sprout\forms\integrations\IntegrationTypeHelper;
use craft\helpers\Cp;
use craft\helpers\Template;
use craft\web\assets\conditionbuilder\ConditionBuilderAsset;
use craft\web\Controller as BaseController;
use yii\web\Response;
use Craft;

class FormIntegrationsController extends BaseController
{
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
