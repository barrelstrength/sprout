<?php

namespace BarrelStrength\Sprout\meta\components\schema;

use BarrelStrength\Sprout\meta\schema\Schema;
use craft\elements\Entry;

class CreativeWorkSchema extends ThingSchema
{
    public function getName(): string
    {
        return 'Creative Work';
    }

    public function getType(): string
    {
        return 'CreativeWork';
    }

    public function isUnlistedSchemaType(): bool
    {
        return false;
    }

    public function addProperties(): void
    {
        parent::addProperties();

        $this->removeProperty('name');

        $this->addText('headline', $this->prioritizedMetadataModel->getOptimizedTitle());
        $this->addText('keywords', $this->prioritizedMetadataModel->getOptimizedKeywords());
        $this->addDate('dateCreated', $this->element->dateCreated);
        $this->addDate('dateModified', $this->element->dateUpdated);

        if ($this->element instanceof Entry) {
            $this->addEntryElementProperties();
        }
    }

    public function addEntryElementProperties(): void
    {
        $identity = $this->globals['identity'];
        $element = $this->element;

        $websiteIdentity = [
            'Person' => WebsiteIdentityPersonSchema::class,
            'Organization' => WebsiteIdentityOrganizationSchema::class,
        ];

        if (isset($element->postDate)) {
            $this->addDate('datePublished', $element->postDate);
        }

        $identityType = $identity['@type'] ?? null;

        if (isset($websiteIdentity[$identityType])) {
            // Determine if we have an Organization or Person Schema Type
            $schemaModel = $websiteIdentity[$identityType];

            /**
             * @var Schema $identitySchema
             */
            $identitySchema = new $schemaModel();

            $identitySchema->globals = $this->globals;

            // Assume the Global Organization or Person is the Creator
            // More specific implementations will require a Custom Schema Integration
            $this->addProperty('author', $identitySchema->getSchema());
            $this->addProperty('creator', $identitySchema->getSchema());
            $this->addProperty('publisher', $identitySchema->getSchema());
        }
    }
}
