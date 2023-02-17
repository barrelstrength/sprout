<?php

namespace BarrelStrength\Sprout\datastudio\visualizations;

use BarrelStrength\Sprout\datastudio\components\visualizations\BarChartVisualization;
use BarrelStrength\Sprout\datastudio\components\visualizations\LineChartVisualization;
use BarrelStrength\Sprout\datastudio\components\visualizations\PieChartVisualization;
use BarrelStrength\Sprout\datastudio\components\visualizations\TimeChartVisualization;
use craft\base\Component;

class Visualizations extends Component
{
    /**
     * Get the list of available visualizations
     */
    public function getVisualizationTypes(): array
    {
        /** @var Visualization[] $visualizationTypes */
        $visualizationTypes = [
            BarChartVisualization::class,
            LineChartVisualization::class,
            PieChartVisualization::class,
            TimeChartVisualization::class,
        ];

        return $visualizationTypes;
    }

    public function getVisualizationOptions(): array
    {
        $visualizationTypes = $this->getVisualizationTypes();

        $options = [
            ['value' => '', 'label' => 'None'],
        ];

        foreach ($visualizationTypes as $visualizationType) {
            $options[] = [
                'value' => $visualizationType,
                'label' => $visualizationType::displayName(),
            ];
        }

        return $options;
    }

    /**
     * Get the list of aggregate functions to use for aggregating visualization data
     */
    public function getAggregates(): array
    {
        $aggregates = [];
        $aggregates[] = ['label' => 'None', 'value' => AggregateMethod::NONE];
        $aggregates[] = ['label' => 'Sum', 'value' => AggregateMethod::SUM];
        $aggregates[] = ['label' => 'Count', 'value' => AggregateMethod::COUNT];
        $aggregates[] = ['label' => 'Average', 'value' => AggregateMethod::AVERAGE];

        return $aggregates;
    }
}
