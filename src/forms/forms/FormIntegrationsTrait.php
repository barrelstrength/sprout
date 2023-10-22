<?php

namespace BarrelStrength\Sprout\forms\forms;

use BarrelStrength\Sprout\core\components\fieldlayoutelements\RelationsTableField;
use BarrelStrength\Sprout\core\twig\TemplateHelper;
use BarrelStrength\Sprout\forms\FormsModule;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\helpers\Template;
use Craft;

trait FormIntegrationsTrait
{
    public function getIntegrationRelationsTableField(): RelationsTableField
    {
        $rows = FormsModule::getInstance()->formIntegrations->getIntegrationsRelationsRows($this);

        $integrationTypes = FormsModule::getInstance()->formIntegrations->getAllIntegrationTypes();
        $options = TemplateHelper::optionsFromComponentTypes($integrationTypes);

        $optionValues = [
            [
                'label' => Craft::t('sprout-module-forms', 'Select Integration Type...'),
                'value' => '',
            ],
        ];

        foreach ($options as $option) {
            $optionValues[] = $option;
        }

        $createSelect = Cp::selectHtml([
            'id' => 'new-integration',
            'name' => 'integrationType',
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
