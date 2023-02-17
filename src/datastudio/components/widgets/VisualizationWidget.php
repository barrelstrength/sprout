<?php

namespace BarrelStrength\Sprout\datastudio\components\widgets;

use BarrelStrength\Sprout\datastudio\components\elements\DataSetElement;
use BarrelStrength\Sprout\datastudio\DataStudioModule;
use Craft;
use craft\base\Widget;

class VisualizationWidget extends Widget
{
    public ?int $dataSetId = null;

    public static function displayName(): string
    {
        return Craft::t('sprout-module-data-studio', 'Sprout Data Set Chart');
    }

    public static function icon(): null|string
    {
        return Craft::getAlias('@Sprout/Assets/dist/static/data-studio/icons/icon-mask.svg');
    }

    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-data-studio/_components/widgets/Visualizations/settings',
            [
                'widget' => $this,
                'dataSets' => DataStudioModule::getInstance()->dataSets->getAllDataSets(),
                'dataSetId' => $this->dataSetId,
            ]);
    }

    public function getTitle(): ?string
    {
        $dataSet = Craft::$app->elements->getElementById($this->dataSetId, DataSetElement::class);

        return $dataSet->name ?? Craft::t('sprout-module-data-studio', 'Sprout Data Sets Chart');
    }

    public function getBodyHtml(): null|string
    {
        /** @var DataSetElement $dataSet */
        $dataSet = Craft::$app->elements?->getElementById($this->dataSetId, DataSetElement::class);
        $dataSource = $dataSet?->getDataSource();

        $labels = $dataSource->getDefaultLabels($dataSet);
        $values = $dataSource->getResults($dataSet);

        if (empty($labels) && !empty($values)) {
            $firstItemInArray = reset($values);
            $labels = array_keys($firstItemInArray);
        }

        $settings = $dataSet->getDataSource()->getSettings();
        $visualization = false;

        if (array_key_exists('visualization', $settings)) {
            $visualization = new $settings['visualization']['type'];
            $visualization->setSettings($settings['visualization']);
            $visualization->setLabels($labels);
            $visualization->setValues($values);
        }

        return Craft::$app->getView()->renderTemplate('sprout-module-data-studio/_components/widgets/Visualizations/body', [
            'title' => 'Data Set Title',
            'visualization' => $visualization,
        ]);
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['dataSetId'], 'number', 'integerOnly' => true];

        return $rules;
    }
}
