<?php

namespace BarrelStrength\Sprout\meta\schema;

use BarrelStrength\Sprout\forms\components\formfields\PhoneFormFieldData;
use BarrelStrength\Sprout\meta\components\meta\OpenGraphMetaType;
use BarrelStrength\Sprout\meta\components\schema\ContactPointSchema;
use BarrelStrength\Sprout\meta\components\schema\GeoSchema;
use BarrelStrength\Sprout\meta\components\schema\ImageObjectSchema;
use BarrelStrength\Sprout\meta\components\schema\MainEntityOfPageSchema;
use BarrelStrength\Sprout\meta\components\schema\PostalAddressSchema;
use BarrelStrength\Sprout\meta\globals\Globals;
use BarrelStrength\Sprout\meta\metadata\Metadata;
use BarrelStrength\Sprout\meta\metadata\OptimizeMetadataHelper;
use BarrelStrength\Sprout\meta\MetaModule;
use Craft;
use craft\base\Element;
use craft\elements\Address;
use craft\helpers\UrlHelper;
use DateTime;
use yii\helpers\Json;

abstract class Schema
{
    /**
     * Defines whether to set the Schema's '@context' property
     */
    public bool $addContext = false;

    /**
     * Defines whether to set the Schema's 'mainEntityOfPage' property
     */
    public bool $isMainEntity = false;

    /**
     * The array of our Structured Data built using the addProperty methods
     * and can later convert this into JsonLD using the getJsonLd() method
     */
    public array $structuredData = [];

    /**
     * The Global Metadata values available to use when building the Structured Data
     */
    public ?Globals $globals = null;

    /**
     * The Matched Element or Primary Element of the schema
     * Each schema implementation can decide what this means.
     * It is often an Element but can also be an array in the
     * case of images because they are saved as an array for
     * global metadata and
     */
    public Element|array|null $element = null;

    /**
     * The result after we optimize data from Globals and Element Metadata
     */
    public ?Metadata $prioritizedMetadataModel = null;

    /**
     * Defines the Schema's `type` property
     */
    public ?string $type = null;

    public function __toString()
    {
        return $this->getType();
    }

    /**
     * The Schema context
     */
    final public function getContext(): string
    {
        return 'http://schema.org/';
    }

    /**
     * Human readable schema name. Admin user will select this schema by this name in the Control Panel.
     */
    abstract public function getName(): string;

    /**
     * Schema.org data type: http://schema.org/docs/full.html
     */
    abstract public function getType(): string;

    /**
     * Determine if the Schema should be listed in the Main Entity dropdown.
     *
     * @example Some schema, such as a PostalAddress may not ever be used as the Main Entity
     *          of the page, but are still be helpful to define to be used within other schema.
     */
    public function isUnlistedSchemaType(): bool
    {
        return false;
    }

    /**
     * Allow Schema definitions to add properties to the the Structured Data array
     * which will be processed and output as Schema
     */
    abstract public function addProperties(): void;

    /**
     * Convert Schema Map attributes to valid JSON-LD
     *
     * This method can return schema data for two different contexts.
     *
     * 1. As JSON-LD for your page
     * 2. As an array for use as a property of another schema
     *
     * By default $this->addContext is set to false, which will make this getSchema
     * method return the schema array without setting the @context property and
     * processing the array of data into JSON-LD. If $this->addContext is set to
     * true, the complete JSON-LD metadata will be returned. It's likely Custom
     * Schema integrations will only need to use the default, as Sprout Meta handles
     * outputting the JSON-LD, but, you never know!
     */
    final public function getSchema(): array|string
    {
        $schema = [];
        $this->addProperties();
        $this->getSchemaOverrideType();

        if (empty($this->structuredData)) {
            return [];
        }

        if ($this->addContext) {
            // Add the @context tag for the full context
            $schema['@context'] = $this->getContext();
        }

        $schema['@type'] = empty($this->type) ? $this->getType() : $this->type;

        foreach ($this->structuredData as $key => $value) {
            // Loop through each array attribute and build the schema
            // depending on what type of attribute 'value' is:
            // '@method' vs. 'value' vs. ???
            $schema[$key] = $value;
        }

        if ($this->addContext) {
            // Return the JSON-LD object, the script tag will be added when output
            return Json::encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        }

        // If context has already been established, just return the data
        return $schema;
    }

    /**
     * Get the dynamic Schema Type Override or fallback to the defined type
     */
    public function getSchemaOverrideType(): string
    {
        $prioritizedMetadataModel = $this->prioritizedMetadataModel;

        if ($prioritizedMetadataModel &&
            $prioritizedMetadataModel->getSchemaOverrideTypeId() !== null &&
            $prioritizedMetadataModel->getSchemaTypeId() === $this::class
        ) {
            $this->type = $prioritizedMetadataModel->getSchemaOverrideTypeId();

            return $this->type;
        }

        return $this->getType();
    }

    /**
     * Allow our schema to define what a generic or fake object will look like
     *
     * @return mixed|null
     */
    public function getMockData(): ?array
    {
        return null;
    }

    /**
     * Add a property to our Structured Data array
     */
    public function addProperty(string $propertyName, array|string $attributes): void
    {
        $this->structuredData[$propertyName] = $attributes;
    }

    /**
     * Remove a property from our Structured Data array
     */
    public function removeProperty(string $propertyName): void
    {
        unset($this->structuredData[$propertyName]);
    }

    /**
     * Add a string to our Structured Data array.
     * If the property is not a string, don't add it.
     */
    public function addText(string $propertyName, string $string): void
    {
        if ($string !== '') {
            $this->structuredData[$propertyName] = $string;
        }
    }

    /**
     * Add a boolean value to our Structured Data array.
     * If the property is not a boolean value, don't add it.
     */
    public function addBoolean(string $propertyName, bool $bool): void
    {
        $this->structuredData[$propertyName] = $bool;
    }

    /**
     * Add a number to our Structured Data array.
     * If the property is not an integer or float, don't add it.
     */
    public function addNumber(string $propertyName, float|int $number): void
    {
        $this->structuredData[$propertyName] = $number;
    }

    /**
     * Add a date to our Structured Data array.
     * If the property is not a date, don't add it.
     *
     * Format the date string into ISO 8601.
     *
     * https://schema.org/Date
     * https://en.wikipedia.org/wiki/ISO_8601
     */
    public function addDate(string $propertyName, DateTime|string $date): void
    {
        $dateTime = $date;

        if (is_string($date)) {
            $dateTime = new DateTime($date);
        }

        $this->structuredData[$propertyName] = $dateTime->format('c');
    }

    /**
     * Add a URL to our Structured Data array.
     * If the property is not a valid URL, don't add it.
     */
    public function addUrl(string $propertyName, string $url): void
    {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            // Valid URL
            $this->structuredData[$propertyName] = $url;
        } else {
            Craft::info('Schema unable to add value. Value is not a valid URL.', __METHOD__);
        }
    }

    /**
     * Add a telephone number to our Structured Data array.
     * If the property is not a string, don't add it.
     *
     * @param mixed[] $phone
     */
    public function addTelephone(string $propertyName, array $phone): void
    {
        if (isset($phone['phone'], $phone['country']) && !empty($phone['phone'])) {
            $phoneModel = new PhoneFormFieldData();
            $phoneModel->country = $phone['country'];
            $phoneModel->phone = $phone['phone'];
            $this->structuredData[$propertyName] = $phoneModel->getInternational();
        } else {
            Craft::info('Schema unable to add value. Value is not a valid Phone.', __METHOD__);
        }
    }

    /**
     * Add an email to our Structured Data array.
     * If the property is not a valid email, don't add it.
     *
     * Additionally, encode the email as HTML entities so it
     * doesn't appear in the output as plain text.
     */
    public function addEmail(string $propertyName, string $email): void
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailString = $this->encodeHtmlEntities('mailto:' . $email);

            // Valid Email
            $this->structuredData[$propertyName] = $emailString;
        } else {
            Craft::info('Schema unable to add value. Value is not a valid Email.', __METHOD__);
        }
    }

    /**
     * Add an image to our Structured Data array as a ImageObjectSchema.
     * If the property is not a valid URL or Asset ID, don't add it.
     *
     * @todo - optimize so any given image is only processed once per page load
     */
    public function addImage($propertyName, $imageId = null): void
    {
        if ($imageId === null) {
            return;
        }

        if (is_string($imageId) && UrlHelper::isFullUrl($imageId)) {
            $meta = $this->prioritizedMetadataModel;
            $image = [
                'url' => $imageId,
            ];
            $openGraphMeta = $meta->getMetaTypes('openGraph');
            if ($openGraphMeta instanceof OpenGraphMetaType) {
                $image['width'] = $openGraphMeta->getOgImageWidth();
                $image['height'] = $openGraphMeta->getOgImageHeight();
            }
        } elseif (is_numeric($imageId)) {
            $imageAsset = Craft::$app->assets->getAssetById($imageId);
            if ($imageAsset === null || !$imageAsset->getUrl()) {
                return;
            }

            $transformSetting = $this->globals->getSettings()['ogTransform'];
            $transform = MetaModule::getInstance()->optimizeMetadata->getSelectedTransform($transformSetting);
            $image = [
                'url' => OptimizeMetadataHelper::getAssetUrl($imageAsset->id, $transformSetting),
                'width' => $imageAsset->getWidth($transform),
                'height' => $imageAsset->getHeight($transform),
            ];
        } else {
            return;
        }

        if (is_countable($image) ? count($image) : 0) {
            $imageObjectSchema = new ImageObjectSchema();
            $imageObjectSchema->element = $image;
            $imageObjectSchema->prioritizedMetadataModel = $this->prioritizedMetadataModel;

            $this->structuredData[$propertyName] = $imageObjectSchema->getSchema();
        }
    }

    /**
     * Add a list of URLs to our Structured Data array.
     * If the property is not an array of URLs, don't add it.
     */
    public function addSameAs(array $urls = []): void
    {
        if ($urls !== []) {
            $sameAsList = $urls;

            $this->structuredData['sameAs'] = array_values($sameAsList);
        }
    }

    /**
     * Add a list of contacts to our Structured Data array as a ContactPointSchema
     * If the property is not an array of contacts, don't add it.
     *
     * @param mixed[] $contacts
     */
    public function addContactPoints(array $contacts = null): void
    {
        if (!$contacts) {
            return;
        }

        $contactPoints = [];

        $contactPointSchema = new ContactPointSchema();

        foreach ($contacts as $contact) {
            $schema = $contactPointSchema;

            $schema->contact = $contact;

            $contactPoints[] = $schema->getSchema();
        }

        $this->structuredData['contactPoint'] = $contactPoints;
    }

    /**
     * Add an Address to our Structured Data array as a PostalAddressSchema
     * If the address ID is not found in our Globals, don't add it.
     */
    public function addAddress($propertyName): void
    {
        $addressModel = $this->globals->addressModel;

        if (!$addressModel instanceof Address) {
            return;
        }

        $address = new PostalAddressSchema();

        $address->addressCountry = $addressModel->countryCode;
        $address->addressLocality = $addressModel->locality;
        $address->addressRegion = $addressModel->administrativeArea;
        $address->postalCode = $addressModel->postalCode;
        $address->streetAddress = $addressModel->addressLine1 . ' ' . $addressModel->addressLine2;
        $this->structuredData[$propertyName] = $address->getSchema();
    }

    /**
     * Add longitude and latitude to our Structured Data array as a GeoSchema
     * If longitude or latitude is not provided, don't add it.
     */
    public function addGeo($propertyName, $latitude, $longitude): void
    {
        if (!$latitude || !$longitude) {
            return;
        }

        $geo = new GeoSchema();

        $geo->latitude = $latitude;
        $geo->longitude = $longitude;

        $this->structuredData[$propertyName] = $geo->getSchema();
    }

    /**
     * Add opening hours to our Structured Data array.
     * Opening hours must be in the correct array format.
     */
    public function addOpeningHours(array $openingHours): void
    {
        $days = [
            0 => 'Su', 1 => 'Mo', 2 => 'Tu', 3 => 'We', 4 => 'Th', 5 => 'Fr', 6 => 'Sa',
        ];

        $index = 0;

        foreach ($openingHours as $value) {
            $openingHours[$index] = $days[$index];

            if (isset($value['open']['time']) && $value['open']['time'] !== '') {
                $time = date('H:i', strtotime($value['open']['time']));
                $openingHours[$index] .= ' ' . $time;
            }

            if (isset($value['close']['time']) && $value['close']['time'] !== '') {
                $time = date('H:i', strtotime($value['close']['time']));
                $openingHours[$index] .= '-' . $time;
            }

            // didn't work this day
            if (strlen($openingHours[$index]) === 2) {
                unset($openingHours[$index]);
            }

            $index++;
        }

        if (array_values($openingHours) !== []) {
            // Prepare opening hours as one dimensional array
            $this->structuredData['openingHours'] = array_values($openingHours);
        }
    }

    /**
     * Add a Main Entity of Page to our Structured Data array of
     * type WebPage using the canonical URL.
     */
    public function addMainEntityOfPage(): void
    {
        $meta = $this->prioritizedMetadataModel;

        $mainEntity = new MainEntityOfPageSchema();
        $mainEntity->type = 'WebPage';
        $mainEntity->id = $meta->getCanonical();

        $mainEntity->prioritizedMetadataModel = $this->prioritizedMetadataModel;

        $this->structuredData['mainEntityOfPage'] = $mainEntity->getSchema();
    }

    /**
     * Returns a string converted to html entities
     * http://goo.gl/LPhtJ
     */
    public function encodeHtmlEntities(string $string): string
    {
        $string = mb_convert_encoding($string, 'UTF-32', 'UTF-8');
        $t = unpack('N*', $string);
        $t = array_map(static function($n) {
            return "&#$n;";
        }, $t);

        return implode('', $t);
    }
}
