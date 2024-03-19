<?php

namespace BarrelStrength\Sprout\meta\metadata;

use BarrelStrength\Sprout\core\Sprout;
use BarrelStrength\Sprout\meta\components\fields\ElementMetadataField;
use BarrelStrength\Sprout\meta\components\schema\WebsiteIdentityOrganizationSchema;
use BarrelStrength\Sprout\meta\components\schema\WebsiteIdentityPersonSchema;
use BarrelStrength\Sprout\meta\components\schema\WebsiteIdentityPlaceSchema;
use BarrelStrength\Sprout\meta\components\schema\WebsiteIdentityWebsiteSchema;
use BarrelStrength\Sprout\meta\globals\Globals;
use BarrelStrength\Sprout\meta\MetaModule;
use BarrelStrength\Sprout\meta\schema\Schema;
use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use yii\base\Component;
use yii\web\View;

class OptimizeMetadata extends Component
{
    public ?Globals $globals = null;

    /**
     * The Element that contains the Element Metadata field for the metadata
     *
     * @var ElementInterface|Element|null
     */
    public ElementInterface|Element|null $element = null;

    /**
     * The first Element Metadata field Metadata from the context
     */
    public ?ElementMetadataField $elementMetadataField = null;

    /**
     * Represents the raw and final versions of the metadata being processed
     */
    public ?Metadata $prioritizedMetadataModel = null;

    /**
     * Any values provided via {% do sprout.modules.meta.meta({}) %} that will take
     * priority over metadata defined in globals or field settings
     */
    public array $templateMetadata = [];

    private ?array $_metadata = null;

    /**
     * Add values to the master $this->templateMetadata array
     *
     * @param mixed[] $meta
     */
    public function updateMeta(array $meta): void
    {
        foreach ($meta as $key => $value) {
            $this->templateMetadata[$key] = $value;
        }
    }

    /**
     * Set the element that matches the $uri
     */
    public function setMatchedElement(int $siteId = null): void
    {
        $path = Craft::$app->getRequest()->getPathInfo();

        $element = Craft::$app->elements->getElementByUri($path, $siteId, true);

        if ($element && $element->uri !== null) {
            $this->element = $element;

            return;
        }

        $this->element = null;
    }

    public function registerMetadata($site): void
    {
        $metadata = $this->getMetadata($site);

        // Renders <title> and <meta> tags at end of <head>
        foreach ($metadata['meta'] as $metaTagTypes) {
            foreach ($metaTagTypes as $name => $content) {
                switch (true) {
                    case ($name === 'title'):
                        Craft::$app->getView()->title = $content;
                        break;
                    case ($name === 'author'):
                    case ($name === 'publisher'):
                    case ($name === 'canonical'):
                        Craft::$app->getView()->registerLinkTag([
                            'rel' => $name,
                            'href' => $content,
                        ]);
                        break;
                    case (str_starts_with($name, 'og')):
                    case (str_starts_with($name, 'article')):
                        Craft::$app->getView()->registerMetaTag([
                            'property' => $name,
                            'content' => $content,
                        ]);
                        break;
                    default:
                        Craft::$app->getView()->registerMetaTag([
                            'name' => $name,
                            'content' => $content,
                        ]);
                }
            }
        }

        /** @var Globals $globals */
        $globals = $metadata['globals'];
        $ownershipTags = $globals->getOwnership();

        foreach ($ownershipTags as $ownershipTag) {
            if (!$ownershipTag['metaTagName'] || !$ownershipTag['metaTagContent']) {
                continue;
            }
            Craft::$app->getView()->registerMetaTag([
                'name' => $ownershipTag['metaTagName'],
                'property' => $ownershipTag['metaTagName'],
                'content' => $ownershipTag['metaTagContent'],
            ]);
        }

        // Renders JSON-LD Schema as <script> tag at end of <body>
        foreach ($metadata['schema'] as $schema) {
            Craft::$app->getView()->registerScript($schema->getSchema(), View::POS_END, [
                'type' => 'application/ld+json',
            ]);
        }
    }

    public function getMetadata($site = null): array|string
    {
        Sprout::beginProfile('OptimizeMetadata::getMetadata');

        if ($this->_metadata !== null) {
            return $this->_metadata;
        }

        $this->setMatchedElement($site->id);

        $this->globals = MetaModule::getInstance()->globalMetadata->getGlobalMetadata($site);
        $this->prioritizedMetadataModel = $this->getPrioritizedMetadataModel();

        $metadata = [
            'globals' => $this->globals,
            'meta' => $this->prioritizedMetadataModel->getMetaTagData(),
            'schema' => $this->getStructuredData($this->element),
        ];

        Sprout::endProfile('OptimizeMetadata::getMetadata');

        return $metadata;
    }

    public function getPrioritizedMetadataModel(): Metadata
    {
        $elementMetadataAttributes = [];

        if ($this->element !== null) {
            $elementMetadataAttributes = MetaModule::getInstance()->elementMetadata->getRawMetadataFromElement($this->element);
        }

        // Only allow Template Overrides if using Pro Edition
        if (MetaModule::isPro() && $this->templateMetadata) {
            /**
             * If an Element ID is provided as an Override, get our Metadata from the Element Metadata Field
             * associated with that Element ID This adds support for using Element Metadata fields on non URL-enabled
             * Elements such as Users and Tags
             *
             * Non URL-Enabled Elements don't resave metadata on their own. That will need to be done manually.
             */
            if ($elementId = $this->templateMetadata['elementId'] ?? null) {
                $element = Craft::$app->elements->getElementById($elementId);

                // Overwrite the Element Attributes if the template override Element ID returns an element
                if ($element) {
                    $elementMetadataAttributes = MetaModule::getInstance()->elementMetadata->getRawMetadataFromElement($element);
                }
            }

            // Merge our attributes overriding the Element attributes with Template overrides
            $attributes = array_filter(array_merge($elementMetadataAttributes, $this->templateMetadata));
        } else {
            $attributes = array_filter($elementMetadataAttributes);
        }

        return new Metadata($attributes);
    }

    public function getStructuredData($element = null): array
    {
        $schema = [];

        $websiteIdentity = [
            'Person' => WebsiteIdentityPersonSchema::class,
            'Organization' => WebsiteIdentityOrganizationSchema::class,
        ];

        $identityType = $this->globals->getIdentity()['@type'] ?? null;

        // Website Identity Schema
        if (isset($websiteIdentity[$identityType])) {
            // Determine if we have an Organization or Person Schema Type
            $schemaModel = $websiteIdentity[$identityType];

            $identitySchema = new $schemaModel();
            $identitySchema->addContext = true;

            $identitySchema->globals = $this->globals;
            $identitySchema->prioritizedMetadataModel = $this->prioritizedMetadataModel;

            if ($element !== null) {
                $identitySchema->element = $element;
            }

            $schema['websiteIdentity'] = $identitySchema;
        }

        // Website Identity Website
        if (isset($this->globals->getIdentity()['name'])) {
            $websiteSchema = new WebsiteIdentityWebsiteSchema();
            $websiteSchema->addContext = true;

            $websiteSchema->globals = $this->globals;
            $websiteSchema->prioritizedMetadataModel = $this->prioritizedMetadataModel;

            if ($element !== null) {
                $websiteSchema->element = $element;
            }

            $schema['website'] = $websiteSchema;
        }

        $identity = $this->globals->getIdentity();

        // Website Identity Place
        if (isset($identity['locationAddressId']) && $identity['locationAddressId']) {
            $placeSchema = new WebsiteIdentityPlaceSchema();
            $placeSchema->addContext = true;

            $placeSchema->globals = $this->globals;
            $placeSchema->prioritizedMetadataModel = $this->prioritizedMetadataModel;

            if ($element !== null) {
                $placeSchema->element = $element;
            }

            $schema['place'] = $placeSchema;
        }

        if ($element !== null && isset($this->elementMetadataField) && $this->elementMetadataField->schemaTypeId) {
            $schema['mainEntity'] = $this->getMainEntityStructuredData($element);
        }

        return $schema;
    }

    public function getMainEntityStructuredData(Element $element): ?Schema
    {
        $schemaTypeId = $this->prioritizedMetadataModel->getSchemaTypeId();

        if (!$schemaTypeId) {
            return null;
        }

        $schema = MetaModule::getInstance()->schemaMetadata->getSchemaByUniqueKey($schemaTypeId);

        if (!$schema) {
            return null;
        }

        $schema->addContext = true;
        $schema->isMainEntity = true;

        $schema->globals = $this->globals;
        $schema->element = $element;
        $schema->prioritizedMetadataModel = $this->prioritizedMetadataModel;

        return $schema;
    }

    /**
     * Return a comma delimited string of robots meta settings
     */
    public function prepareRobotsMetadataValue(array|string|null $robots = null): ?string
    {
        if ($robots === null) {
            return null;
        }

        if (is_string($robots)) {
            return $robots;
        }

        $robotsMetaValue = '';

        foreach ($robots as $key => $value) {
            if ($value == '') {
                continue;
            }

            if ($robotsMetaValue == '') {
                $robotsMetaValue .= $key;
            } else {
                $robotsMetaValue .= ',' . $key;
            }
        }

        return empty($robotsMetaValue) ? null : $robotsMetaValue;
    }

    /**
     * Return an array of all robots settings set to their boolean value of on or off
     */
    public function prepareRobotsMetadataForSettings($robotsValues): array
    {
        if (is_string($robotsValues)) {
            $robotsArray = explode(',', $robotsValues);

            $robotsSettings = [];

            foreach ($robotsArray as $value) {
                $robotsSettings[$value] = 1;
            }
        } else {
            // Value from content table
            $robotsSettings = $robotsValues;
        }

        $robots = [
            'noindex' => 0,
            'nofollow' => 0,
            'noarchive' => 0,
            'noimageindex' => 0,
            'noodp' => 0,
            'noydir' => 0,
        ];

        foreach (array_keys($robots) as $key) {
            if (isset($robotsSettings[$key]) && $robotsSettings[$key]) {
                $robots[$key] = 1;
            }
        }

        return $robots;
    }

    public function getImageId($image)
    {
        $imageId = $image;

        if (is_array($image)) {
            $imageId = $image[0];
        }

        return $imageId ?? null;
    }

    /**
     * Return pre-defined transform settings or the selected transform handle
     */
    public function getSelectedTransform($transformHandle): ?array
    {
        $defaultTransforms = [
            'sprout-socialSquare' => [
                'mode' => 'crop',
                'width' => 400,
                'height' => 400,
                'quality' => 82,
                'position' => 'center-center',
            ],
            'sprout-ogRectangle' => [
                'mode' => 'crop',
                'width' => 1200,
                'height' => 630,
                'quality' => 82,
                'position' => 'center-center',
            ],
            'sprout-twitterRectangle' => [
                'mode' => 'crop',
                'width' => 1024,
                'height' => 512,
                'quality' => 82,
                'position' => 'center-center',
            ],
        ];

        return $defaultTransforms[$transformHandle] ?? ($transformHandle == '' ? null : $transformHandle);
    }
}
