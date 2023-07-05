<?php

namespace BarrelStrength\Sprout\forms\formtemplates;

interface FormTemplateSetInterface
{
    public function name(): ?string;

    public static function isEditable(): bool;

    //public function getFieldLayout(): FieldLayout;
}
