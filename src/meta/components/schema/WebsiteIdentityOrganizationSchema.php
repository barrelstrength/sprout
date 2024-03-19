<?php

namespace BarrelStrength\Sprout\meta\components\schema;

use BarrelStrength\Sprout\meta\schema\Schema;
use Craft;

class WebsiteIdentityOrganizationSchema extends Schema
{
    public ?string $type = 'Organization';

    public function getName(): string
    {
        return 'Organization';
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isUnlistedSchemaType(): bool
    {
        return true;
    }

    /**
     * Does syntax user a generic `object` or do we need to assume
     * we know specifically what the variable is called?
     *
     * Have some out of box helper methods like getFirst()
     * Do we really need the @methodName syntax? or do we just write this in PHP?
     */
    public function addProperties(): void
    {
        $schema = $this->globals['identity'];
        $socialProfiles = $this->globals['social'];

        $this->setOrganizationType($schema);

        $this->addText('name', $schema['name']);
        $this->addText('alternateName', $schema['alternateName']);
        $this->addText('description', $schema['description']);
        $this->addUrl('url', Craft::$app->sites->getCurrentSite()->getBaseUrl());
        $this->addTelephone('telephone', $schema['telephone']);
        $this->addEmail('email', $schema['email']);

        if (isset($schema['image'])) {
            $this->addImage('image', $schema['image']);
        }

        // Add Corporate Contacts
        $contacts = $this->globals['contacts'] ?? null;

        $this->addContactPoints($contacts);

        if (isset($schema['organizationSubTypes'][0]) && $schema['organizationSubTypes'][0] == 'LocalBusiness') {
            $openingHours = $schema['openingHours'] ?? null;

            $this->addOpeningHours($openingHours);
        }

        if (isset($schema['locationAddressId']) && $schema['locationAddressId']) {
            $this->addAddress('locationAddressId');
        }

        if (isset($schema['foundingDate']['date'])) {
            $this->addDate('foundingDate', $schema['foundingDate']['date']);
        }

        if (isset($schema['priceRange'])) {
            $this->addText('priceRange', $schema['priceRange']);
        }

        if (is_array($socialProfiles) && count($socialProfiles)) {
            $urls = array_column($socialProfiles, 'url');
            $this->addSameAs($urls);
        }
    }

    /**
     * Process the selected Organization Type setting and update this schema type
     */
    protected function setOrganizationType($schema): void
    {
        $organization = [];
        $organization['organizationSubTypes'] = [];
        $organization['organizationSubTypes'][0] = $schema['organizationSubTypes'][0] ?? null;
        $organization['organizationSubTypes'][1] = $schema['organizationSubTypes'][1] ?? null;
        $organization['organizationSubTypes'][2] = $schema['organizationSubTypes'][2] ?? null;

        // Set the right value for @type
        foreach ($organization['organizationSubTypes'] as $org) {
            if ($org != '') {
                $this->type = $org;
            }
        }
    }
}
