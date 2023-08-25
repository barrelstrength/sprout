<?php

namespace BarrelStrength\Sprout\mailer\components\emailthemes\fieldlayoutfields;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\TextareaField;

class OptionalDefaultMessageField extends DefaultMessageField
{
    public bool $mandatory = false;
}
