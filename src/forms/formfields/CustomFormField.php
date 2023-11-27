<?php

namespace BarrelStrength\Sprout\forms\formfields;

use craft\fieldlayoutelements\CustomField;

class CustomFormField extends CustomField
{
    protected function settingsHtml(): ?string
    {
        $field = $this->getField();

        return $field->getSettingsHtml();
    }
}
