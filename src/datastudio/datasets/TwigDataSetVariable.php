<?php

namespace BarrelStrength\Sprout\datastudio\datasets;

use BarrelStrength\Sprout\datastudio\DataStudioModule;

class TwigDataSetVariable
{
    /**
     * Add a header row to your Data Set
     */
    public function addHeaderRow(array $row): void
    {
        DataStudioModule::getInstance()->customTwigTemplates->addHeaderRow($row);
    }

    /**
     * Add a single row of data to your Data Set
     */
    public function addRow(array $row): void
    {
        DataStudioModule::getInstance()->customTwigTemplates->addRow($row);
    }

    /**
     * Add multiple rows of data to your Data Set
     *
     * @example [
     *   [ ... ],
     *   [ ... ],
     * ]
     */
    public function addRows(array $rows): void
    {
        DataStudioModule::getInstance()->customTwigTemplates->addRows($rows);
    }
}
