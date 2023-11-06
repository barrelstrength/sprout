<?php

namespace BarrelStrength\Sprout\datastudio\visualizations;

use craft\base\SavableComponent;
use craft\helpers\Html;

/**
 *
 * @property array $timeSeries
 * @property array $settings
 * @property string $decimals
 * @property array $dataSeries
 * @property string $aggregate
 */
abstract class Visualization extends SavableComponent implements VisualizationInterface
{
    /**
     * if this is a date time Data Set stores the earliest timestamp value from the data series
     */
    protected int $startDate = 0;

    /**
     * if this is a date time Data Set stores the latest timestamp value from the data series
     */
    protected int $endDate = 0;

    protected array $dataColumns;

    protected string $labelColumn;

    /**
     * Set the visualization raw data values
     */
    protected array $values;

    /**
     * Set the visualization labels
     */
    protected array $labels;

    /**
     * Returns the first (earliest) timestamp value from the data series for a time series visualization
     *
     * @returns int timestamp in milliseconds
     */
    public function getStartDate(): int
    {
        return $this->startDate;
    }

    /**
     * Returns the last (latest) timestamp value (in milliseconds) from the data series for a time series visualization
     */
    public function getEndDate(): int
    {
        return $this->endDate;
    }

    /**
     * Returns an array of the defined data columns
     */
    public function getDataColumns(): array
    {
        //        if (!$this->settings) {
        //            return [];
        //        }
        //
        if (isset($this->dataColumns) && $this->dataColumns) {
            return $this->dataColumns;
        }

        if (isset($this->dataColumn)) {
            return [$this->dataColumn];
        }

        return [''];
    }

    /**
     * Returns the label column
     */
    public function getLabelColumn(): string
    {
        if ($this->settings && array_key_exists('labelColumn', $this->settings)) {
            return $this->settings['labelColumn'];
        }

        return false;
    }

    /**
     * Returns the aggregate setting
     */
    public function getAggregate(): string
    {
        if ($this->settings && array_key_exists('aggregate', $this->settings)) {
            return $this->settings['aggregate'];
        }

        return false;
    }

    /**
     * Returns the decimals setting
     */
    public function getDecimals(): string
    {
        if ($this->settings && array_key_exists('decimals', $this->settings)) {
            return $this->settings['decimals'];
        }

        return 0;
    }

    public function setValues(array $values): void
    {
        $this->values = $values;
    }

    public function getLabels(): array
    {
        $labelColumn = $this->getLabelColumn();
        $labels = [];

        if ($labelColumn) {
            $labelIndex = array_search($labelColumn, $this->labels, true);
            foreach ($this->values as $row) {
                $labels[] = array_key_exists($labelColumn, $row) ? $row[$labelColumn] : $row[$labelIndex];
            }
        }

        return $labels;
    }

    /**
     * @param mixed[] $labels
     */
    public function setLabels(array $labels): void
    {
        $this->labels = $labels;
    }

    public function namespaceInputId($name): string
    {
        return 'visualizationSettings-' . Html::id(static::class) . '-' . $name;
    }

    public function namespaceInputName($name): string
    {
        return 'visualizationSettings[' . static::class . '][' . $name . ']';
    }

    public function getSettingsHtml(): string
    {
        return '';
    }

    /**
     * Return the data series for each defined data column.
     * Each series contains a 'name' and 'data' value
     */
    public function getDataSeries(): array
    {
        $dataColumns = $this->getDataColumns();

        $dataSeries = [];
        foreach ($dataColumns as $dataColumn) {
            $data = [];

            foreach ($this->values as $row) {
                if (array_key_exists($dataColumn, $row)) {
                    $data[] = $row[$dataColumn];
                } else {
                    $dataIndex = array_search($dataColumn, $this->labels, true);
                    $data[] = $row[$dataIndex];
                }
            }

            $dataSeries[] = ['name' => $dataColumn, 'data' => $data];
        }

        return $dataSeries;
    }

    /**
     * Return the data series for each defined data column.
     * Each series contains a 'name' and 'data' value
     */
    public function getTimeSeries(): array
    {
        $dataColumns = $this->getDataColumns();
        $labelColumn = $this->getLabelColumn();
        $aggregate = $this->getAggregate();
        $decimals = $this->getDecimals();

        $dataSeries = [];
        foreach ($dataColumns as $dataColumn) {
            $data = [];

            foreach ($this->values as $row) {
                $point = [];
                if (array_key_exists($dataColumn, $row)) {
                    $value = $row[$dataColumn];
                } else {
                    $dataIndex = array_search($dataColumn, $this->labels, true);
                    $value = $row[$dataIndex];
                }

                $point['y'] = $value;

                if (array_key_exists($labelColumn, $row)) {
                    $point['x'] = $row[$labelColumn];
                } else {
                    $labelIndex = array_search($labelColumn, $this->labels, true);
                    $point['x'] = $row[$labelIndex];
                }

                //convert value to timestamp
                //incoming date format should be in ISO-8601 format, ie 2020-04-27T15:19:21+00:00
                //in Twig this entry.postDate|date('c')
                $time = strtotime($point['x']);

                if ($time) {
                    $time *= 1000;
                    $point['x'] = $time;

                    if ($this->startDate == 0 || $time < $this->startDate) {
                        $this->startDate = $time;
                    }

                    if ($this->endDate == 0 || $time > $this->endDate) {
                        $this->endDate = $time;
                    }
                }

                if ($aggregate) {
                    //check to see if time value exists in data set,
                    //if not create as array to values into for aggregate calculation
                    if (array_key_exists($point['x'], $data) == false) {
                        $data[$point['x']] = [];
                    }

                    $data[$point['x']][] = $point['y'];
                } else {
                    $data[] = $point;
                }
            }

            //aggregate the data values
            if ($aggregate) {
                $aggregateData = [];
                foreach ($data as $key => $row) {
                    if (is_callable([$this, $aggregate])) {
                        $aggregateData[] = [
                            'x' => $key,
                            'y' => number_format($this->$aggregate($row), $decimals),
                        ];
                    }
                }

                $data = $aggregateData;
            }

            //sort data based on the 'x' (time) attribute
            usort($data, function($a, $b): int {
                return $this->timeSort($a, $b);
            });

            $dataSeries[] = ['name' => $dataColumn, 'data' => $data];
        }

        return $dataSeries;
    }

    private function timeSort($a, $b): int
    {
        return $a['x'] <=> $b['x'];
    }

    /**
     * Aggregate method dynamically called based on Visualization settings
     *
     * @noinspection PhpUnusedPrivateMethodInspection
     */
    private function sum($values): float|int
    {
        return array_sum($values);
    }

    /**
     * Aggregate method dynamically called based on Visualization settings
     *
     * @noinspection PhpUnusedPrivateMethodInspection
     */
    private function count($values): int
    {
        return is_countable($values) ? count($values) : 0;
    }

    /**
     * Aggregate method dynamically called based on Visualization settings
     *
     * @noinspection PhpUnusedPrivateMethodInspection
     */
    private function average($values): float|int
    {
        return array_sum($values) / (is_countable($values) ? count($values) : 0);
    }
}
