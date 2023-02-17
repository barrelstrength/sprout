<?php

namespace BarrelStrength\Sprout\meta\components\schema;

use BarrelStrength\Sprout\meta\schema\Schema;
use Craft;

class WebsiteIdentityPlaceSchema extends Schema
{
    public function getName(): string
    {
        return 'Place';
    }

    public function getType(): string
    {
        return 'Place';
    }

    public function isUnlistedSchemaType(): bool
    {
        return true;
    }

    public function addProperties(): void
    {
        $schema = $this->globals['identity'];
        $socialProfiles = $this->globals['social'];

        $this->addText('name', $schema['name']);
        $this->addText('alternateName', $schema['alternateName']);
        $this->addText('description', $schema['description']);
        $this->addUrl('url', Craft::$app->sites->getCurrentSite()->getBaseUrl());

        if (isset($schema['image'])) {
            $this->addImage('image', $schema['image']);
        }

        $this->addTelephone('telephone', $schema['telephone']);

        if (isset($schema['address']) && $schema['address']) {
            $this->addAddress('address');
        }

        if (isset($schema['latitude'], $schema['longitude']) && $schema['latitude'] && $schema['longitude']) {
            $this->addGeo('geo', $schema['latitude'], $schema['longitude']);
        }

        if (is_array($socialProfiles) && count($socialProfiles)) {
            $urls = array_column($socialProfiles, 'url');
            $this->addSameAs($urls);
        }
    }
}
