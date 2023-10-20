<?php

namespace BarrelStrength\Sprout\forms\forms;

use BarrelStrength\Sprout\datastudio\components\events\ModifyDataSourceRelationsQueryEvent;
use BarrelStrength\Sprout\forms\components\elements\FormElement;
use yii\db\Expression;
use Craft;

class FormsHelper
{
    public static function modifyDataSourceRelationsQuery(ModifyDataSourceRelationsQueryEvent $event): void
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

