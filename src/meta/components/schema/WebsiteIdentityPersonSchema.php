<?php

namespace BarrelStrength\Sprout\meta\components\schema;

use BarrelStrength\Sprout\meta\schema\Schema;
use Craft;

class WebsiteIdentityPersonSchema extends Schema
{
    public function getName(): string
    {
        return 'Person';
    }

    public function getType(): string
    {
        return 'Person';
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
        $this->addTelephone('telephone', $schema['telephone']);
        $this->addEmail('email', $schema['email']);

        if (isset($schema['address']) && $schema['address']) {
            $this->addAddress('address');
        }

        if (isset($schema['image'])) {
            $this->addImage('image', $schema['image']);
        }

        $contacts = $this->globals['contacts'];
        $this->addContactPoints($contacts);

        $this->addText('gender', $schema['gender']);

        if (is_array($socialProfiles) && count($socialProfiles)) {
            $urls = array_column($socialProfiles, 'url');
            $this->addSameAs($urls);
        }
    }
}
