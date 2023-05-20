<?php

namespace BarrelStrength\Sprout\meta\components\fields;

use BarrelStrength\Sprout\meta\metadata\Metadata;
use BarrelStrength\Sprout\meta\MetaModule;
use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\db\mysql\Schema;
use craft\helpers\Html;
use craft\helpers\Json;

class ElementMetadataField extends Field
{
    public ?string $optimizedTitleField = null;

    public ?string $optimizedTitleFieldFormat = null;

    public ?string $optimizedDescriptionField = null;

    public ?string $optimizedDescriptionFieldFormat = null;

    public ?string $optimizedImageField = null;

    public ?string $optimizedImageFieldFormat = null;

    public ?string $optimizedKeywordsField = null;

    public ?string $schemaTypeId = null;

    public ?string $schemaOverrideTypeId = null;

    public bool $editCanonical = false;

    public bool $enableMetaDetailsFields = false;

    public bool $showSearchMeta = false;

    public bool $showOpenGraph = false;

    public bool $showTwitter = false;

    public bool $showGeo = false;

    public bool $showRobots = false;

    public static function displayName(): string
    {
        return Craft::t('sprout-module-meta', 'Metadata (Sprout)');
    }

    public function getContentColumnType(): array|string
    {
        return Schema::TYPE_TEXT;
    }

    public function isValueEmpty(mixed $value, ElementInterface $element): bool
    {
        if (!$value instanceof Metadata) {
            return true;
        }

        $attributes = array_filter($value->getRawData());

        return $attributes === [];
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element = null): Metadata
    {
        if ($value instanceof Metadata) {
            return $value;
        }

        $metadataArray = [];

        // On page load and the resave element task the $value comes from the content table as json
        if (is_string($value)) {
            $metadataArray = Json::decode($value);
        }

        // when is resaving on all sites comes into array
        if (is_array($value)) {
            $metadataArray = $value;
        }

        // When is a post request the metadata values comes into the metadata key
        if (isset($value['metadata'])) {
            $metadataArray = $value['metadata'];
        }

        if (isset($metadataArray['metaSettings'])) {
            // removes json value from livepreview
            unset($metadataArray['metaSettings']);
        }

        $this->populateOptimizeServiceValues($element);

        return new Metadata($metadataArray, true);
    }

    public function serializeValue(mixed $value, ?ElementInterface $element = null): ?array
    {
        if ($value instanceof Metadata && $element) {
            $isEmpty = $this->isValueEmpty($value, $element);
            if (!$isEmpty) {
                return $value->getRawData();
            }
        }

        return null;
    }

    public function getSettingsHtml(): ?string
    {
        $schemas = MetaModule::getInstance()->schemaMetadata->getSchemaOptions();
        $schemaSubtypes = MetaModule::getInstance()->schemaMetadata->getSchemaSubtypes($schemas);

        return Craft::$app->view->renderTemplate('sprout-module-meta/_components/fields/ElementMetadata/settings.twig', [
            'fieldId' => $this->id,
            'settings' => $this->getAttributes(),
            'field' => $this,
            'schemas' => $schemas,
            'schemaSubtypes' => $schemaSubtypes,
        ]);
    }

    public function getInputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        $name = $this->handle;
        $inputId = Html::id($name);
        $namespaceInputName = Craft::$app->view->namespaceInputName($inputId);
        $namespaceInputId = Craft::$app->view->namespaceInputId($inputId);

        // Cleanup the namespace around the $name handle
        $name = str_replace('fields[', '', $name);
        $name = rtrim($name, ']');

        $fieldId = 'fields-' . $name . '-field';

        $name = "meta[metadata][{$name}]";

        $settings = $this->getAttributes();

        return Craft::$app->view->renderTemplate('sprout-module-meta/_components/fields/ElementMetadata/input.twig', [
            'field' => $this,
            'name' => $name,
            'namespaceInputName' => $namespaceInputName,
            'namespaceInputId' => $namespaceInputId,
            'metaTypes' => $value->getMetaTypes(),
            'values' => $value->getRawData(),
            'fieldId' => $fieldId,
            'settings' => $settings,
        ]);
    }

    protected function defineRules(): array
    {
        $isPro = MetaModule::isPro();
        $metadataFieldCount = (int)MetaModule::getInstance()->elementMetadata->getMetadataFieldCount();

        $theFirstMetadataField = !$this->id && $metadataFieldCount === 0;
        $theOneMetadataField = $this->id && $metadataFieldCount === 1;

        if (!$isPro && (!$theFirstMetadataField && !$theOneMetadataField)) {
            $this->addError('optimizedTitleField', Craft::t('sprout-module-meta', 'Upgrade to Sprout Meta PRO to manage multiple Metadata fields.'));
        }

        return parent::defineRules();
    }

    protected function populateOptimizeServiceValues(ElementInterface $element = null): void
    {
        /** @var Element $element */
        $site = isset($element)
            ? Craft::$app->sites->getSiteById($element->siteId)
            : Craft::$app->sites->getPrimarySite();

        $globals = MetaModule::getInstance()->globalMetadata->getGlobalMetadata($site);

        MetaModule::getInstance()->optimizeMetadata->globals = $globals;

        MetaModule::getInstance()->optimizeMetadata->element = $element;
        MetaModule::getInstance()->optimizeMetadata->elementMetadataField = $this;
    }
}
