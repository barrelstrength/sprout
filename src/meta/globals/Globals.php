<?php

namespace BarrelStrength\Sprout\meta\globals;

use BarrelStrength\Sprout\meta\MetaModule;
use Craft;
use craft\base\Model;
use craft\elements\Address;
use DateTime;

class Globals extends Model
{
    public ?int $id = null;

    public ?int $siteId = null;

    public ?array $identity = null;

    public array $ownership = [];

    public array $contacts = [];

    public array $social = [];

    public ?array $robots = null;

    public ?array $settings = null;

    public ?DateTime $dateCreated = null;

    public ?DateTime $dateUpdated = null;

    public ?string $uid = null;

    public ?Address $addressModel = null;

    public function __construct($config = [])
    {
        if (isset($config['identity'])) {
            if (isset($config['identity']['telephone']) && is_string($config['identity']['telephone'])) {
                $config['identity']['telephone'] = [];
            }
        }
        if (isset($config['contacts'])) {
            foreach ($config['contacts'] as $key => $contact) {
                if (empty($contact['contactType']) && empty($contact['telephone'])) {
                    unset($config['contacts'][$key]);
                }
            }
        }

        if (isset($config['social'])) {
            foreach ($config['social'] as $key => $row) {
                if (empty($row['url']) && empty($row['profileName'])) {
                    unset($config['social'][$key]);
                }
            }
        }

        if (isset($config['ownership'])) {
            foreach ($config['ownership'] as $key => $row) {
                if (empty($row['metaTagName']) && empty($row['metaTagContent'])) {
                    unset($config['ownership'][$key]);
                }
            }
        }

        parent::__construct($config);
    }

    public function init(): void
    {
        if (isset($this->identity['locationAddressId'])) {
            $elementId = $this->identity['locationAddressId'];

            /** @var Address $address */
            $address = Craft::$app->getElements()->getElementById($elementId);
            $this->addressModel = $address;
        } else {
            $address = new Address();
            $address->title = Craft::t('sprout-module-meta', 'Address');
            Craft::$app->getElements()->saveElement($address);
            $this->addressModel = $address;
        }

        parent::init();
    }

    /**
     * Factory to return schema of any type
     * Most settings return an array, but robots returns a string
     */
    public function getGlobalByKey(string $target = null): array|string|null
    {
        if (!$target) {
            return null;
        }

        $targetMethod = 'get' . ucfirst($target);

        $schema = $this->{$targetMethod}();

        return $schema;
    }

    public function getWebsiteIdentityType(): ?string
    {
        $this->getGlobalByKey('identity');
        $identityType = 'Organization';

        if (isset($this->identity['@type']) && $this->identity['@type'] != '') {
            $identityType = $this->identity['@type'];
        }

        return $identityType;
    }

    /**
     * Determine if the selected Website Identity Schema Type is a Local Business
     */
    public function isLocalBusiness(): bool
    {
        $this->getGlobalByKey('identity');

        return isset($this->identity['organizationSubTypes'][0]) && $this->identity['organizationSubTypes'][0] === 'LocalBusiness';
    }

    /**
     * Get the values associated with the Identity column in the database
     */
    public function getIdentity(): ?array
    {
        if (isset($this->identity['image']) && is_array($this->identity['image'])) {
            $this->identity['image'] = $this->identity['image'][0] ?? null;
        }

        if (isset($this->addressModel)) {
            $this->identity['locationAddressId'] = $this->addressModel->id;
        }

        return $this->identity;
    }

    /**
     * Get the values associated with the Contacts column in the database
     */
    public function getContacts(): ?array
    {
        $contacts = $this->contacts;
        $contactPoints = null;

        foreach ($contacts as $contact) {
            $contactPoints[] = [
                '@type' => 'ContactPoint',
                'contactType' => $contact['contactType'] ?? $contact[0],
                'telephone' => $contact['telephone'] ?? $contact[1],
            ];
        }

        return $contactPoints;
    }

    /**
     * Get the values associated with the Social column in the database
     */
    public function getSocial(): ?array
    {
        $profiles = $this->social;

        $profileLinks = null;

        foreach ($profiles as $profile) {
            $profileLinks[] = [
                'profileName' => $profile['profileName'] ?? $profile[0],
                'url' => $profile['url'] ?? $profile[1],
            ];
        }

        return $profileLinks;
    }

    /**
     * Get the values associated with the Ownership column in the database
     */
    public function getOwnership(): ?array
    {
        return $this->ownership;
    }

    public function getRobots(): ?array
    {
        return $this->robots;
    }

    /**
     * Get the values associated with the Settings column in the database
     */
    public function getSettings(): ?array
    {
        return $this->settings;
    }
}
