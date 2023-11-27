<?php

namespace BarrelStrength\Sprout\datastudio\datasets;

use BarrelStrength\Sprout\datastudio\components\elements\DataSetElement;
use BarrelStrength\Sprout\datastudio\datasources\DataSource;
use BarrelStrength\Sprout\datastudio\DataStudioModule;
use BarrelStrength\Sprout\datastudio\db\SproutTable;
use Craft;
use craft\db\Query;
use craft\helpers\Html;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use craft\models\Site;
use Twig\Markup;

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

    public static function getNewDataSetButtonHtml(Site $site, array $dataSourceTypes = null, string $styleClass = 'submit'): ?Markup
    {
        $dataSourceTypes = $dataSourceTypes ?? DataStudioModule::getInstance()->dataSources->getDataSourceTypes();

        $newDataSetOptions = [];

        foreach ($dataSourceTypes as $dataSourceType) {
            $currentUser = Craft::$app->getUser()->getIdentity();

            if (!$currentUser->can(DataStudioModule::p('editDataSet:' . $dataSourceType))) {
                continue;
            }

            $newDataSetOptions[] = [
                'name' => $dataSourceType::displayName(),
                'url' => UrlHelper::cpUrl('sprout/data-studio/new', [
                    'type' => $dataSourceType,
                    'site' => $site->handle,
                ]),
            ];
        }

        $label = Craft::t('sprout-module-data-studio', 'New {displayName}', [
            'displayName' => DataSetElement::displayName(),
        ]);

        $labelHtml = Html::button($label, [
            'class' => 'btn menubtn add icon ' . $styleClass,
        ]);

        $menuListHtml = Html::ul($newDataSetOptions, [
            'item' => function($item) {
                return Html::tag('li', Html::a($item['name'], $item['url'], [
                    'class' => 'formsubmit sprout-dataset-new-button',
                ]));
            },
        ]);

        $menuHtml = Html::tag('div', $menuListHtml, [
            'class' => 'menu',
        ]);

        $buttonHtml = Html::tag('div', $labelHtml . $menuHtml, [
            'id' => 'sprout-new-dataset-btn',
            'class' => 'btngroup',
        ]);

        return Template::raw($buttonHtml);
    }

    // @todo - can probably remove this in favor of a more concise options method
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
