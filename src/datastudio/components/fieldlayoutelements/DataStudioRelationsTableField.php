<?php

namespace BarrelStrength\Sprout\datastudio\components\fieldlayoutelements;

use BarrelStrength\Sprout\core\components\fieldlayoutelements\RelationsTableField;

class DataStudioRelationsTableField extends RelationsTableField
{
    public array $rows = [];

    public function __construct($config = [])
    {
        parent::__construct($config);
    }
}
