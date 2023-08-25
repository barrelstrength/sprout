<?php

namespace BarrelStrength\Sprout\mailer\emailtypes;

use craft\models\FieldLayout;

interface EmailTypeInterface
{
    public static function isEditable(): bool;

    public function getFieldLayout(): FieldLayout;
}
