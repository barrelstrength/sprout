<?php

namespace BarrelStrength\Sprout\forms\forms;

use BarrelStrength\Sprout\forms\db\SproutTable;
use craft\db\ActiveRecord;
use craft\records\Element;
use yii\db\ActiveQueryInterface;

/**
 * @property int $id
 * @property string $submissionFieldLayout
 * @property string $fieldLayoutSettings
 * @property string $name
 * @property string $handle
 * @property string $titleFormat
 * @property bool $displaySectionTitles
 * @property Element $element
 * @property string $redirectUri
 * @property string $submissionMethod
 * @property string $errorDisplayMethod
 * @property string $messageOnSuccess
 * @property string $messageOnError
 * @property string $submitButtonText
 * @property bool $saveData
 * @property string $formTypeUid
 * @property bool $enableCaptchas
 * @property string $oldHandle
 */
class FormRecord extends ActiveRecord
{
    private string $_oldHandle;

    public static function tableName(): string
    {
        return SproutTable::FORMS;
    }

    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }
}
