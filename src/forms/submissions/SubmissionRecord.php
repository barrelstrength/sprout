<?php

namespace BarrelStrength\Sprout\forms\submissions;

use BarrelStrength\Sprout\forms\db\SproutTable;
use BarrelStrength\Sprout\forms\forms\FormRecord;
use craft\db\ActiveRecord;
use craft\records\Element;
use yii\db\ActiveQueryInterface;

/**
 * @property int $id
 * @property string $formId
 * @property int $statusId
 * @property string $title
 * @property string $ipAddress
 * @property string $userAgent
 * @property ActiveQueryInterface $element
 * @property ActiveQueryInterface $submissionStatuses
 * @property ActiveQueryInterface $form
 */
class SubmissionRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return SproutTable::FORM_SUBMISSIONS;
    }

    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }

    public function getForm(): ActiveQueryInterface
    {
        return $this->hasMany(FormRecord::class, ['formId' => 'id']);
    }

    public function getSubmissionStatuses(): ActiveQueryInterface
    {
        return $this->hasMany(SubmissionStatusRecord::class, ['statusId' => 'id']);
    }
}
