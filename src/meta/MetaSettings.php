<?php

namespace BarrelStrength\Sprout\meta;

use craft\config\BaseConfig;

class MetaSettings extends BaseConfig
{
    public bool $enableRenderMetadata = true;

    public int $maxMetaDescriptionLength = 160;

    public function enableRenderMetadata(bool $value): self
    {
        $this->enableRenderMetadata = $value;

        return $this;
    }

    public function maxMetaDescriptionLength(int $value): self
    {
        $this->maxMetaDescriptionLength = $value;

        return $this;
    }
}
