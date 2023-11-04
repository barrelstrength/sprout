<?php

namespace BarrelStrength\Sprout\forms\forms;

use BarrelStrength\Sprout\core\components\fieldlayoutelements\RelationsTableField;
use BarrelStrength\Sprout\core\twig\TemplateHelper;
use BarrelStrength\Sprout\forms\FormsModule;
use BarrelStrength\Sprout\forms\integrations\IntegrationTypeHelper;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\helpers\Template;
use Craft;

trait FormIntegrationsTrait
{
    public function getIntegrationRelationsTableField(): RelationsTableField
    {
        $rows = FormsModule::getInstance()->formIntegrations->getIntegrationsRelationsRows($this);

        $enabledIntegrationTypes = $this->getFormType()?->enabledIntegrationTypes ?? [];
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
        $sidebarHtml = Html::tag('div', Html::tag('p', $sidebarMessage) , [
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
