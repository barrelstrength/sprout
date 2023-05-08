<?php

namespace BarrelStrength\Sprout\forms\forms;

use BarrelStrength\Sprout\core\relations\SourceElementRelationsEvent;
use BarrelStrength\Sprout\forms\components\elements\FormElement;
use BarrelStrength\Sprout\transactional\components\elements\TransactionalEmailElement;

class FormHelper
{
    public static function getSourceElementRelations(SourceElementRelationsEvent $event): void
    {
        $element = $event->targetElement;
        if (!$element instanceof FormElement) {
            return;
        }

        $emails = TransactionalEmailElement::find()
            ->all();

        $event->sourceElements[] = $emails;
    }
}

