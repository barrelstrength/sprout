<?php

namespace BarrelStrength\Sprout\mailer\audience;

use craft\base\SavableComponent;

abstract class AudienceType extends SavableComponent implements AudienceTypeInterface
{
    //    public bool $isSuppressionList = false;

    public string $emailColumn = 'email';

    public string $nameColumn = 'name';

    public function getColumnAttributeHtml(): string
    {
        return '';
    }
}
