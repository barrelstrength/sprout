<?php

namespace BarrelStrength\Sprout\datastudio\datasources;

use BarrelStrength\Sprout\datastudio\components\elements\DataSetElement;
use craft\base\Plugin;

trait DataSourceTrait
{
    /**
     * Tracks the export state to allow conditional DataSet behavior based on how a DataSource is being used
     */
    public bool $isExport = false;

    protected Plugin $plugin;

    protected DataSetElement $dataSet;
}
