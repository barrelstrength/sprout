<?php

namespace BarrelStrength\Sprout\datastudio\components\visualizations;

use BarrelStrength\Sprout\datastudio\visualizations\Visualization;
use Craft;

class BarChartVisualization extends Visualization
{
    public string $labelColumn = '';

    public array $dataColumns = [''];

    public static function displayName(): string
    {
        return Craft::t('sprout-module-data-studio', 'Bar Chart');
    }

    public static function handle(): string
    {
        return 'barChart';
    }

    public function getSettingsHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-data-studio/_components/visualizations/BarChart/settings', [
            'visualization' => $this,
        ]);
    }

    public function getVisualizationHtml(array $options = []): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-data-studio/_components/visualizations/BarChart/visualization', [
            'visualization' => $this,
            'options' => $options,
        ]);
    }
}
