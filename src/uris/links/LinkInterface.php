<?php

namespace BarrelStrength\Sprout\uris\links;

interface LinkInterface
{
    public static function displayName(): string;

    public function getInputHtml(): ?string;

    /**
     * Returns the input’s ID, which the `<label>`’s `for` attribute should reference.
     *
     * @return string
     * @since 3.7.32
     */
    //public function getInputId(): string;
}
