<?php

namespace BarrelStrength\Sprout\forms\components\formfeatures;

use BarrelStrength\Sprout\core\components\fieldlayoutelements\RelationsTableField;
use BarrelStrength\Sprout\core\relations\RelationsTableInterface;
use BarrelStrength\Sprout\forms\components\elements\FormElement;
use BarrelStrength\Sprout\forms\components\events\DefineFormFeatureSettingsEvent;
use BarrelStrength\Sprout\forms\components\events\RegisterFormFeatureTabsEvent;
use BarrelStrength\Sprout\forms\FormsModule;
use BarrelStrength\Sprout\forms\integrations\IntegrationTypeHelper;
use Craft;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\models\FieldLayoutTab;

class WorkflowTabFormFeature implements RelationsTableInterface
{
    public static function defineFormTypeSettings(DefineFormFeatureSettingsEvent $event): void
    {
        $integrationTypes = IntegrationTypeHelper::getIntegrationTypes();

        $integrationSettings = [];
        foreach ($integrationTypes as $uid => $integrationType) {
            $integrationSettings[$uid] = $integrationType->name;
        }

        $event->featureSettings[self::class] = [
            'label' => Craft::t('sprout-module-forms', 'Enable Integrations'),
            'settings' => $integrationSettings,
        ];
    }

    public static function registerWorkflowTab(RegisterFormFeatureTabsEvent $event): void
    {
        $element = $event->element ?? null;

        if (!$element instanceof FormElement) {
            return;
        }

        $formType = $element->getFormType();
        $featureSettings = $formType->featureSettings[self::class] ?? [];
        $enableTab = $featureSettings['enabled'] ?? false;

        if (!$enableTab) {
            return;
        }

        $fieldLayout = $event->fieldLayout;

        Craft::$app->getView()->registerJs('new IntegrationsRelationsTable(' . $element->id . ', ' . $element->siteId . ');');

        $integrationsTab = new FieldLayoutTab();
        $integrationsTab->layout = $fieldLayout;
        $integrationsTab->name = Craft::t('sprout-module-forms', 'Workflows');
        $integrationsTab->uid = 'SPROUT-UID-FORMS-INTEGRATIONS-TAB';
        $integrationsTab->sortOrder = 60;
        $integrationsTab->setElements([
            self::getRelationsTableField($element),
        ]);

        $event->tabs[] = $integrationsTab;
    }

    public static function getRelationsTableField($element): RelationsTableField
    {
        $rows = FormsModule::getInstance()->formIntegrations->getIntegrationsRelationsRows($element);

        $formType = $element->getFormType();
        $featureSettings = $formType->featureSettings[self::class] ?? [];

        $enabledIntegrationTypes = $featureSettings['settings'] ?? [];
        $savedIntegrationTypes = IntegrationTypeHelper::getIntegrationTypes();

        // Remove any integration types that are not enabled
        foreach ($savedIntegrationTypes as $uid => $integrationType) {
            if (!array_key_exists($uid, $enabledIntegrationTypes)) {
                unset($savedIntegrationTypes[$uid]);
            }
        }

        $optionValues = [
            [
                'label' => Craft::t('sprout-module-forms', 'Select Workflow...'),
                'value' => '',
            ],
        ];

        foreach ($savedIntegrationTypes as $uid => $savedIntegrationType) {
            $optionValues[$uid] = $savedIntegrationType->name;
        }

        $createSelect = Cp::selectHtml([
            'id' => 'new-integration',
            'name' => 'integrationTypeUid',
            'options' => $optionValues,
            'value' => '',
        ]);

        $sidebarMessage = Craft::t('sprout-module-forms', 'Configure an integration to perform actions with your form data after submission.');
        $sidebarHtml = Html::tag('div', Html::tag('p', $sidebarMessage), [
            'class' => 'meta read-only',
        ]);

        return new RelationsTableField([
            'attribute' => 'integrations',
            'rows' => $rows,
            'newButtonHtml' => $createSelect,
            'sidebarHtml' => $sidebarHtml,
        ]);
    }
}
