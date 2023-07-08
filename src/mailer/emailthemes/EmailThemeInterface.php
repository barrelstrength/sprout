<?php

namespace BarrelStrength\Sprout\mailer\emailthemes;

use craft\models\FieldLayout;

interface EmailThemeInterface
{
    public static function isEditable(): bool;

    public function getFieldLayout(): FieldLayout;
}
