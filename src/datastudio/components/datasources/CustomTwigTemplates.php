<?php

namespace BarrelStrength\Sprout\datastudio\components\datasources;

use craft\base\Component;

class CustomTwigTemplates extends Component
{
    /**
     * Determine if a results template has been run already
     */
    public bool $hasRun = false;

    /**
     * A single array representing the column headers of the first row of a data set
     */
    public array $labels = [];

    /**
     * Variable that is used to build data sets row by row
     *
     * @example
     * [
     *   0 => [
     *        'column' => 1,
     *        'column2' => 2
     *   ],
     *   1 => [
     *      'column' => 1,
     *      'column2' => 2
     *   ]
     * ];
     */
    public array $rows = [];

    public function addHeaderRow(array $row): void
    {
        $this->labels = $row;
    }

    public function addRow(array $row): void
    {
        $this->rows[] = $row;
    }

    /**
     * Add multiple rows of data to your data set
     *
     * @example [[ ... ],[ ... ]]
     */
    public function addRows(array $rows): void
    {
        foreach ($rows as $row) {
            $this->addRow($row);
        }
    }
}
