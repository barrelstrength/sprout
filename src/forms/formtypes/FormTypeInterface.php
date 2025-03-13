<?php

namespace BarrelStrength\Sprout\forms\formtypes;

interface FormTypeInterface
{
    public function getIncludeTemplates(): array;

    public function getCustomTemplatesFolder(): ?string;

    public function getRenderTemplatesFolder(): ?string;

    public function getDefaultTemplatesFolder(): ?string;

    //public function getFieldLayout(): FieldLayout;

    /**
     * @todo - Craft requires this because it assumes only Elements have field layouts.
     * This is not true with how we've built the Email Element.
     */
    public static function defaultCardAttributes(): array;
}
