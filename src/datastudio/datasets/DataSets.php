<?php

namespace BarrelStrength\Sprout\datastudio\datasets;

use BarrelStrength\Sprout\datastudio\components\elements\DataSetElement;
use BarrelStrength\Sprout\datastudio\datasources\DataSource;
use BarrelStrength\Sprout\datastudio\db\SproutTable;
use craft\db\Query;
use yii\base\Component;

class DataSets extends Component
{
    public function getAllDataSets(): array
    {
        $rows = (new Query())
            ->select('dataSet.*')
            ->from(['dataSet' => SproutTable::DATASETS])
            ->all();

        return $this->populateDataSets($rows);
    }

    public function getDataSetAsSelectFieldOptions(): array
    {
        $options = [];

        $dataSets = $this->getAllDataSets();

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

    public function getCountByDataSourceType(string $type): int
    {
        $totalDataSetsForDataSource = DataSetRecord::find()
            ->where([
                'type' => $type,
            ])
            ->count();

        return (int)$totalDataSetsForDataSource;
    }

    public function getLabelsAndValues(DataSetElement $dataSet, DataSource $dataSource): array
    {
        $labels = $dataSource->getDefaultLabels($dataSet);

        $values = $dataSource->getResults($dataSet);

        if (empty($labels) && !empty($values)) {
            $firstItemInArray = reset($values);
            $labels = array_keys($firstItemInArray);
        }

        return [$labels, $values];
    }

    private function populateDataSets($rows): array
    {
        $dataSets = [];

        if ($rows) {
            foreach ($rows as $row) {

                $model = new DataSetElement();
                $model->setAttributes($row, false);
                $dataSets[] = $model;
            }
        }

        return $dataSets;
    }
}
