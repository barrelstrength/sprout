<?php

namespace BarrelStrength\Sprout\datastudio\components\elements\fieldlayoutelements;

use BarrelStrength\Sprout\datastudio\components\elements\DataSetElement;
use BarrelStrength\Sprout\datastudio\DataStudioModule;
use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseNativeField;

class VisualizationsSettingsField extends BaseNativeField
{
    public string $attribute = 'visualizations';

    protected function useFieldset(): bool
    {
        return true;
    }

    protected function showLabel(): bool
    {
        return false;
    }

    protected function selectorLabel(): ?string
    {
        return Craft::t('sprout-module-data-studio', 'Visualization Settings');
    }

    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        if (!$element instanceof DataSetElement) {
            return '';
        }

        $visualizationTypes = DataStudioModule::getInstance()->visualizations->getVisualizationTypes();
        $visualizationOptions = DataStudioModule::getInstance()->visualizations->getVisualizationOptions();

        return Craft::$app->getView()->renderTemplate('sprout-module-data-studio/_datasets/visualizations.twig', [
            'dataSet' => $element,
            'visualizationTypes' => $visualizationTypes,
            'visualizationOptions' => $visualizationOptions,
            'isPro' => DataStudioModule::isPro(),
        ]);
    }

}
