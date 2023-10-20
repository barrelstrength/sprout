<?php

namespace BarrelStrength\Sprout\datastudio\components\events;

use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use craft\base\ElementInterface;
use craft\db\ActiveQuery;
use craft\elements\db\ElementQuery;
use yii\base\Event;

class ModifyDataSourceRelationsQueryEvent extends Event
{
    public ElementInterface $element;

    public ElementQuery $query;
}
