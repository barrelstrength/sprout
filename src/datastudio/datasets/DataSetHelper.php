<?php

namespace BarrelStrength\Sprout\datastudio\datasets;

use BarrelStrength\Sprout\datastudio\components\elements\DataSetElement;
use BarrelStrength\Sprout\datastudio\datasources\DataSource;
use BarrelStrength\Sprout\datastudio\db\SproutTable;
use craft\db\Query;

class DataSetHelper
{
    public static function getAllDataSets(): array
    {
        $rows = (new Query())
            ->select('dataSet.*')
            ->from(['dataSet' => SproutTable::DATASETS])
            ->all();

        if ($rows) {
            foreach ($rows as $row) {

                $model = new DataSetElement();
                $model->setAttributes($row, false);
                $dataSets[] = $model;
            }
        }

        return $dataSets ?? [];
    }

    public static function getDataSetAsSelectFieldOptions(): array
    {
        $options = [];

        $dataSets = self::getAllDataSets();

        if ($dataSets) {
            foreach ($dataSets as $dataSet) {
                $options[] = [
                    'label' => $dataSet->name,
                    'value' => $dataSet->getId(),
                ];
            }
        }

        return $options;
    }

    public static function getCountByDataSourceType(string $type): int
    {
        $totalDataSetsForDataSource = DataSetRecord::find()
            ->where([
                'type' => $type,
            ])
            ->count();

        return (int)$totalDataSetsForDataSource;
    }

    public static function getLabelsAndValues(DataSetElement $dataSet, DataSource $dataSource): array
    {
        $labels = $dataSource->getDefaultLabels($dataSet);

        $values = $dataSource->getResults($dataSet);

        if (empty($labels) && !empty($values)) {
            $firstItemInArray = reset($values);
            $labels = array_keys($firstItemInArray);
        }

        return [$labels, $values];
    }
}
