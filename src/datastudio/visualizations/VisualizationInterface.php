<?php

namespace BarrelStrength\Sprout\datastudio\visualizations;

use craft\base\SavableComponentInterface;

interface VisualizationInterface extends SavableComponentInterface
{
    /**
     * Return the visualization HTML
     *
     * @param array $options override values passed to the javascript charting instance
     *
     * @return string The HTML that displays the chart/visualization
     */
    public function getVisualizationHtml(array $options = []): string;

    /**
     * Returns the column names to be used as the data series.
     */
    public function getDataColumns(): array;

    /**
     * Returns the column name to be used as the label series
     */
    public function getLabelColumn(): ?string;
}
