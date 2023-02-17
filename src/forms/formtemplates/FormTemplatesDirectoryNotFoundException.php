<?php

namespace BarrelStrength\Sprout\forms\formtemplates;

use yii\base\Exception;

class FormTemplatesDirectoryNotFoundException extends Exception
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName(): string
    {
        return 'Form Templates directory not found';
    }
}
