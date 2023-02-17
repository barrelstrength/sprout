<?php

namespace BarrelStrength\Sprout\datastudio\components\visualizations;

use BarrelStrength\Sprout\datastudio\visualizations\Visualization;
use Craft;

class PieChartVisualization extends Visualization
{
    public string $labelColumn = '';

    public string $dataColumn = '';

    public static function displayName(): string
    {
        return Craft::t('sprout-module-data-studio', 'Pie Chart');
    }

    public static function handle(): string
    {
        return 'pieChart';
    }

    public function getSettingsHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-data-studio/_components/visualizations/PieChart/settings', [
            'visualization' => $this,
        ]);
    }

    public function getVisualizationHtml(array $options = []): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-data-studio/_components/visualizations/PieChart/visualization', [
            'visualization' => $this,
            'options' => $options,
        ]);
    }
}
