<?php

namespace BarrelStrength\Sprout\meta\metadata;

use craft\base\Component;
use craft\base\Field;

/**
 * @property string $handle
 * @property array $attributesMapping
 * @property string $settingsHtml
 * @property array $staticAttributes
 * @property string $iconPath
 * @property array $rawData
 * @property array $metaTagData
 */
abstract class MetaType extends Component
{
    public function __construct($config = [], protected ?Metadata $metadata = null)
    {
        parent::__construct($config);
    }

    /**
     * By default, expect metadata attributes to be matched to their exact name
     */
    public function getAttributesMapping(): array
    {
        $mapping = [];

        foreach (array_keys($this->getAttributes()) as $key) {
            $mapping[$key] = $key;
        }

        return $mapping;
    }

    /**
     * The handle that will be used to reference this meta type in templates
     */
    abstract public function getHandle(): string;

    /**
     * The icon that will display when displaying the Meta Details edit tab
     */
    public function getIconPath(): string
    {
        return '';
    }

    /**
     * Whether this meta type supports meta details override settings. Implement getSettingsHtml() if so.
     */
    public function hasMetaDetails(): bool
    {
        return true;
    }

    /**
     * Whether to display a tab for users to edit meta details
     */
    public function showMetaDetailsTab(): bool
    {
        return false;
    }

    /**
     * The settings to display on the Meta Details edit tab
     */
    public function getSettingsHtml(Field $field): string
    {
        return '';
    }

    /**
     * Just the attributes we need to save to the db
     */
    public function getRawData(): array
    {
        return array_keys($this->getAttributes());
    }

    /**
     * Prepares the metadata for front-end use with calculated values
     */
    public function getMetaTagData(): array
    {
        $tagData = [];

        foreach ($this->getAttributes() as $key => $value) {
            $getter = 'get' . ucfirst($key);
            if (method_exists($this, $getter)) {
                $value = $this->{$getter}();

                $metaTagName = $this->getMetaTagName($key);

                // Meta tag not supported in mapping.
                // For example, twitterTransform exists for settings but not on the front-end
                if (!$metaTagName) {
                    continue;
                }

                // Make sure all our strings are trimmed
                $tagData[$metaTagName] = is_string($value) ? trim($value) : $value;
            }
        }

        return $tagData;
    }

    protected function getMetaTagName($handle)
    {
        $tagNames = $this->getAttributesMapping();

        return $tagNames[$handle] ?? null;
    }
}
