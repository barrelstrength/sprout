<?php

namespace BarrelStrength\Sprout\meta\schema;

trait SchemaTrait
{
    protected ?string $schemaTypeId = null;

    protected ?string $schemaOverrideTypeId = null;

    public function getSchemaTypeId(): ?string
    {
        return $this->schemaTypeId;
    }

    public function setSchemaTypeId($value): void
    {
        $this->schemaTypeId = $value;
    }

    public function getSchemaOverrideTypeId(): ?string
    {
        return $this->schemaOverrideTypeId;
    }

    public function setSchemaOverrideTypeId($value): void
    {
        $this->schemaOverrideTypeId = $value;
    }
}
