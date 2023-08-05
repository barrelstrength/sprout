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

    public function getSettingsHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-data-studio/_components/visualizations/BarChart/settings.twig', [
            'visualization' => $this,
        ]);
    }

    public function getVisualizationHtml(array $options = []): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-data-studio/_components/visualizations/BarChart/visualization.twig', [
            'visualization' => $this,
            'options' => $options,
        ]);
    }
}
