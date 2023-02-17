<?php

namespace BarrelStrength\Sprout\datastudio\reports;

use craft\helpers\Json;
use League\Csv\ByteSequence;
use League\Csv\Writer;
use SplTempFileObject;
use yii\base\Component;

class Exports extends Component
{
    public function toJson(array &$values): string
    {
        return Json::encode($values);
    }

    /**
     * Takes an array of values and options labels and creates a downloadable CSV file
     */
    public function toCsv(array &$values, array $labels = [], string $filename = 'export.csv', $delimiter = null): void
    {
        $filename = str_replace('.csv', '', $filename) . '.csv';

        if (empty($labels) && !empty($values)) {
            $arrayValues = array_values($values);
            $firstRowOfArray = array_shift($arrayValues);

            $labels = array_keys($firstRowOfArray);
        }

        $csv = Writer::createFromFileObject(new SplTempFileObject());
        $csv->setOutputBOM(ByteSequence::BOM_UTF8);

        // Defaults to comma-delimited
        if ($delimiter) {
            $csv->setDelimiter($delimiter);
        }

        $csv->insertOne($labels);
        $csv->insertAll($values);
        $csv->output($filename);

        exit(0);
    }
}
