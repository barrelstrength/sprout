<?php

namespace BarrelStrength\Sprout\forms\formfields;

use craft\base\ElementInterface;
use craft\base\Field;

class FormFieldHelper
{
    /**
     * Returns current Field Type context to properly get field settings
     */
    public static function getFieldContext(Field $field, ElementInterface $element = null): string
    {
        $context = 'global';

        if ($field->context) {
            $context = $field->context;
        }

        if ($element !== null) {
            $context = $element->getFieldContext();
        }

        return $context;
    }
}

