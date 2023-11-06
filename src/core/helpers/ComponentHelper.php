<?php

namespace BarrelStrength\Sprout\core\helpers;

class ComponentHelper
{
    public static function typesToInstances(array $types = []): array
    {
        return array_map(static function($type) {
            return new $type();
        }, $types);
    }
}
