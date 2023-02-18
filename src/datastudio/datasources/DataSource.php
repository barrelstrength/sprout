<?php

namespace BarrelStrength\Sprout\datastudio\datasources;

use BarrelStrength\Sprout\datastudio\components\elements\DataSetElement;
use BarrelStrength\Sprout\datastudio\datasets\DataSetHelper;
use craft\base\SavableComponent;

abstract class DataSource extends SavableComponent implements DataSourceInterface
{
    use DataSourceTrait;

    /**
     * Set a DataSet on our data source.
     */
    public function setDataSet(DataSetElement $dataSet = null): void
    {
        if ($dataSet === null) {
            $dataSet = new DataSetElement();
        }

        $this->dataSet = $dataSet;
    }

    public function getDescription(): string
    {
        return '';
    }

    public function getDefaultLabels(DataSetElement $dataSet): array
    {
        return [];
    }

    public function getResults(DataSetElement $dataSet): array
    {
        return [];
    }

    /**
     * Give a Data Source a chance to prepare settings before they are processed by the Dynamic Name field
     */
    public function prepSettings(array $settings): array
    {
        return $settings;
    }

    /**
     * Allow a user to toggle the Allow Html setting.
     */
    public function isAllowHtmlEditable(): bool
    {
        return false;
    }

    /**
     * Define the default value for the Allow HTML setting. Setting Allow HTML
     * to true enables a Data Set to output HTML on the Results page.
     */
    public function getDefaultAllowHtml(): bool
    {
        return false;
    }

    /**
     * Returns the total count of Data Sets created based on the given data source
     */
    final public function getDataSetCount(): int
    {
        return DataSetHelper::getCountByDataSourceType(self::class);
    }
}
