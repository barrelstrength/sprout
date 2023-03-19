<?php

namespace BarrelStrength\Sprout\mailer\emailthemes;

use craft\models\FieldLayout;

interface EmailThemeInterface
{
    public function name(): ?string;

    public static function isEditable(): bool;
    
    public function getFieldLayout(): FieldLayout;
}
