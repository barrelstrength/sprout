<?php

namespace BarrelStrength\Sprout\meta\metadata;

use BarrelStrength\Sprout\meta\MetaModule;
use craft\models\Site;

class MetadataVariable
{
    /**
     * Sets SEO metadata in templates
     *
     * @param array<string, mixed> $meta Array of supported meta values
     */
    public function meta(array $meta = []): void
    {
        if ($meta !== []) {
            MetaModule::getInstance()->optimizeMetadata->updateMeta($meta);
        }
    }

    public function getDivider($site = null): string
    {
        $globals = MetaModule::getInstance()->globalMetadata->getGlobalMetadata($site);

        $divider = '';

        if (isset($globals['settings']['metaDivider'])) {
            $divider = $globals->getSettings()['metaDivider'];
        }

        return $divider;
    }

    public function getContacts(Site $currentSite = null): array
    {
        $contacts = MetaModule::getInstance()->globalMetadata->getGlobalMetadata($currentSite)->getContacts();

        $contacts = $contacts ?: [];

        foreach ($contacts as &$contact) {
            $contact['type'] = $contact['contactType'];
            unset($contact['contactType']);
        }

        return $contacts;
    }

    public function getSocialProfiles(Site $currentSite = null): array
    {
        $socials = MetaModule::getInstance()->globalMetadata->getGlobalMetadata($currentSite)->getSocial();

        $socials = $socials ?: [];

        foreach ($socials as &$social) {
            $social['name'] = $social['profileName'];
            unset($social['profileName']);
        }

        return $socials;
    }
}
