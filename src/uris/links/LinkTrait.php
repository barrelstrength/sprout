<?php

namespace BarrelStrength\Sprout\uris\links;

use Craft;

trait LinkTrait
{
    public ?string $url = null;

    public function __toString(): string
    {
        return $this->getUrl() ?? static::class;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function toArray(array $fields = [], array $expand = [], $recursive = true): array
    {
        $data = parent::toArray($fields, $expand, $recursive);

        $data['type'] = static::class;

        unset(
            $data['id'],
            $data['dateCreated'],
            $data['dateUpdated'],
        );

        return $data;
    }

    public function namespaceInputName(string $name): string
    {
        return Craft::$app->getView()->namespaceInputName($name, static::class);
    }
}
