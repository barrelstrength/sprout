<?php

namespace BarrelStrength\Sprout\core\components\events;

use craft\base\ElementInterface;
use craft\elements\db\ElementQuery;
use yii\base\Event;

class ModifyRelationsTableQueryEvent extends Event
{
    public ElementInterface $element;

    public ElementQuery $query;
}
