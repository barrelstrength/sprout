<?php

namespace BarrelStrength\Sprout\core\relations;

use craft\base\Element;
use yii\base\Event;

class SourceElementRelationsEvent extends Event
{
    public Element $targetElement;

    // Should populated by plugin that registers the event
    public string $sourceElementType;

    /**
     * @var Element[] $sourceElements
     */
    public array $sourceElements = [];
}
