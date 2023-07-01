<?php

namespace BarrelStrength\Sprout\transactional\notificationevents;

interface ElementEventInterface
{
    public static function conditionType(): string;

    public static function elementType(): string;
}
