<?php

namespace BarrelStrength\Sprout\mailer\audience;

use craft\base\Element;
use craft\base\SavableComponent;

abstract class AudienceType extends SavableComponent implements AudienceTypeInterface
{
    /**
     * The Element ID of the current AudienceType instance
     */
    public ?Element $element = null;

    //    public bool $isSuppressionList = false;

    public string $emailColumn = 'email';

    public string $nameColumn = 'name';

    public function getColumnAttributeHtml(): string
    {
        return '';
    }
}
