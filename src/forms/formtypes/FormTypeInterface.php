<?php

namespace BarrelStrength\Sprout\forms\formtypes;

interface FormTypeInterface
{
    public static function isEditable(): bool;

    //public function getFieldLayout(): FieldLayout;
}
