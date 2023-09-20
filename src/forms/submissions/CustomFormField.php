<?php

namespace BarrelStrength\Sprout\forms\submissions;

use craft\fieldlayoutelements\CustomField;

class CustomFormField extends CustomField
{
    protected function settingsHtml(): ?string
    {
        return '<p>Form Field Settings</p>';
    }
}
