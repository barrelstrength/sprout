<?php

namespace BarrelStrength\Sprout\datastudio\datasources;

use BarrelStrength\Sprout\datastudio\components\elements\DataSetElement;
use craft\base\SavableComponentInterface;

interface DataSourceInterface extends SavableComponentInterface
{
    /**
     * A description for the Data Source
     */
    public function getDescription(): string;

    /**
     * Should return an array of strings to be used as column headings in display/output
     */
    public function getDefaultLabels(DataSetElement $dataSet): array;

    /**
     * Should return an array of records to use in the data set
     */
    public function getResults(DataSetElement $dataSet): array;
}
