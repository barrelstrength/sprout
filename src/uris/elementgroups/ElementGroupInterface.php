<?php

namespace BarrelStrength\Sprout\uris\elementgroups;

/**
 * Defines interface that makes it possible to loop through
 * Element Types and determine their Element Group without
 * having to know the Element Type
 */
interface ElementGroupInterface
{
    public function defineElementGroups($siteId): array;
}
