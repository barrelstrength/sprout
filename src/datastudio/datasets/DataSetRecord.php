<?php

namespace BarrelStrength\Sprout\datastudio\datasets;

use BarrelStrength\Sprout\datastudio\db\SproutTable;
use craft\base\Element;
use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * @property int $id
 * @property string $name
 * @property string $nameFormat
 * @property string $handle
 * @property string $description
 * @property string $type
 * @property bool $allowHtml
 * @property string $sortOrder
 * @property string $sortColumn
 * @property string $delimiter
 * @property string $visualizationType
 * @property string $visualizationSettings
 * @property string $settings
 * @property bool $enabled
 * @property ActiveQueryInterface $element
 */
class DataSetRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return SproutTable::DATASETS;
    }

    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }
}
