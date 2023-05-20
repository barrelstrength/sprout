<?php

namespace BarrelStrength\Sprout\datastudio\components\visualizations;

use BarrelStrength\Sprout\datastudio\DataStudioModule;
use BarrelStrength\Sprout\datastudio\visualizations\Visualization;
use Craft;

class TimeChartVisualization extends Visualization
{
    public string $labelColumn = '';

    public array $dataColumns = [];

    public string $aggregate = 'sum';

    public int $decimals = 0;

    public static function displayName(): string
    {
        return Craft::t('sprout-module-data-studio', 'Time Series');
    }

    public static function handle(): string
    {
        return 'timeChart';
    }

    public function getSettingsHtml(): string
    {
        $visualizationAggregateOptions = DataStudioModule::getInstance()->visualizations->getAggregates();

        return Craft::$app->getView()->renderTemplate('sprout-module-data-studio/_components/visualizations/TimeChart/settings.twig', [
            'visualization' => $this,
            'visualizationAggregateOptions' => $visualizationAggregateOptions,
        ]);
    }

    public function getVisualizationHtml(array $options = []): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-data-studio/_components/visualizations/TimeChart/visualization.twig', [
            'visualization' => $this,
            'options' => $options,
        ]);
    }
}
