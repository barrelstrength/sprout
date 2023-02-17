<?php

namespace BarrelStrength\Sprout\meta;

use craft\config\BaseConfig;

class MetaSettings extends BaseConfig
{
    public bool $enableRenderMetadata = true;

    public bool $useMetadataVariable = false;

    public string $metadataVariableName = 'metadata';

    public int $maxMetaDescriptionLength = 160;

    public function enableRenderMetadata(bool $value): self
    {
        $this->enableRenderMetadata = $value;

        return $this;
    }

    public function useMetadataVariable(bool $value): self
    {
        $this->useMetadataVariable = $value;

        return $this;
    }

    public function metadataVariableName(string $value): self
    {
        $this->metadataVariableName = $value;

        return $this;
    }

    public function maxMetaDescriptionLength(int $value): self
    {
        $this->maxMetaDescriptionLength = $value;

        return $this;
    }
}

