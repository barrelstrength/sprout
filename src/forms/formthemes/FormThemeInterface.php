<?php

namespace BarrelStrength\Sprout\forms\formthemes;

interface FormThemeInterface
{
    public static function isEditable(): bool;

    //public function getFieldLayout(): FieldLayout;
}
