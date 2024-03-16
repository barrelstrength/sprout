<?php

namespace BarrelStrength\Sprout\meta;

use craft\config\BaseConfig;

class MetaSettings extends BaseConfig
{
    public bool $renderMetadata = true;

    public int $maxMetaDescriptionLength = 160;

    public function renderMetadata(bool $value): self
    {
        $this->renderMetadata = $value;

        return $this;
    }

    public function maxMetaDescriptionLength(int $value): self
    {
        $this->maxMetaDescriptionLength = $value;

        return $this;
    }
}
