<?php

namespace BarrelStrength\Sprout\meta\components\schema;

use BarrelStrength\Sprout\meta\schema\Schema;
use Craft;

class WebsiteIdentityWebsiteSchema extends Schema
{
    public function getName(): string
    {
        return 'Website';
    }

    public function getType(): string
    {
        return 'Website';
    }

    public function isUnlistedSchemaType(): bool
    {
        return true;
    }

    public function addProperties(): void
    {
        $schema = $this->globals['identity'];
        $socialProfiles = $this->globals['social'];

        $websiteIdentity = [
            'Person' => WebsiteIdentityPersonSchema::class,
            'Organization' => WebsiteIdentityOrganizationSchema::class,
        ];

        $this->addText('name', $schema['name']);
        $this->addText('alternateName', $schema['alternateName']);
        $this->addText('description', $schema['description']);
        $this->addText('keywords', $schema['keywords']);
        $this->addUrl('url', Craft::$app->sites->getCurrentSite()->getBaseUrl());

        if (isset($schema['image'])) {
            $this->addImage('image', $schema['image']);
        }

        $identityType = $schema['@type'];

        if (isset($websiteIdentity[$identityType])) {
            // Determine if we have an Organization or Person Schema Type
            $schemaModel = $websiteIdentity[$identityType];

            /** @var Schema $identitySchema */
            $identitySchema = new $schemaModel();

            $identitySchema->globals = $this->globals;
            $identitySchema->element = $this->element;
            $identitySchema->prioritizedMetadataModel = $this->prioritizedMetadataModel;

            $this->addProperty('author', $identitySchema->getSchema());
            $this->addProperty('copyrightHolder', $identitySchema->getSchema());
            $this->addProperty('creator', $identitySchema->getSchema());
        }

        if (is_array($socialProfiles) && count($socialProfiles)) {
            $urls = array_column($socialProfiles, 'url');
            $this->addSameAs($urls);
        }
    }
}
