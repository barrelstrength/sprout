<?php

namespace BarrelStrength\Sprout\uris\links;

interface LinkInterface
{
    public static function displayName(): string;

    public function getInputHtml(): ?string;
}
