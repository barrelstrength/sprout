<?php

namespace BarrelStrength\Sprout\forms\forms;

use BarrelStrength\Sprout\core\components\events\ModifyRelationsTableQueryEvent;
use BarrelStrength\Sprout\forms\components\elements\FormElement;
use Craft;
use yii\db\Expression;

class FormsHelper
{
    public static function modifyDataSourceRelationsQuery(ModifyRelationsTableQueryEvent $event): void
    {
        $element = $event->element;

        if (!$element instanceof FormElement) {
            return;
        }

        if (Craft::$app->getDb()->getIsPgsql()) {
            $expression = new Expression('JSON_EXTRACT(sprout_datasets.settings, "formId")');
        } else {
            $expression = new Expression('JSON_EXTRACT(sprout_datasets.settings, "$.formId")');
        }

        $event->query->andWhere(['=', $expression, $element->id]);
    }
}

