<?php

namespace BarrelStrength\Sprout\uris\links;

use Craft;
use craft\i18n\PhpMessageSource;

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

    //public function getInputId(): string
    //{
    //    return Html::id($this->handle);
    //}
}
