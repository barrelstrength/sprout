<?php

namespace BarrelStrength\Sprout\meta\metadata;

use BarrelStrength\Sprout\meta\components\meta\GeoMetaType;
use BarrelStrength\Sprout\meta\components\meta\OpenGraphMetaType;
use BarrelStrength\Sprout\meta\components\meta\RobotsMetaType;
use BarrelStrength\Sprout\meta\components\meta\SearchMetaType;
use BarrelStrength\Sprout\meta\components\meta\TwitterMetaType;
use BarrelStrength\Sprout\meta\MetaModule;
use BarrelStrength\Sprout\meta\schema\SchemaTrait;
use craft\base\Model;

class Metadata extends Model
{
    use OptimizedTrait;
    use MetaImageTrait;
    use SchemaTrait;

    /**
     * @var MetaType[]
     */
    protected array $metaTypes = [];

    /**
     * DISABLE for backend when raw. ENABLE for front-end when MetaTags.
     */
    protected bool $rawDataOnly = false;

    public function __construct($config = [], $rawDataOnly = false)
    {
        // Remove any null or empty string values from the provided configuration
        $config = array_filter($config);

        $this->setRawDataOnly($rawDataOnly);

        // Populate the Optimized variables and unset them from the config
        $this->setOptimizedProperties($config);

        // Populate the MetaType models and unset any attributes that get assigned
        $this->setMetaTypes($config);

        // Schema properties will be derived from global and field settings
        $this->setSchemaProperties();

        parent::__construct($config);
    }

    public function getRawDataOnly(): bool
    {
        return $this->rawDataOnly;
    }

    public function setRawDataOnly(bool $value): void
    {
        $this->rawDataOnly = $value;
    }

    public function attributes(): array
    {
        $attributes = parent::attributes();
        $attributes[] = 'optimizedTitle';
        $attributes[] = 'optimizedDescription';
        $attributes[] = 'optimizedImage';
        $attributes[] = 'optimizedKeywords';
        $attributes[] = 'canonical';

        return $attributes;
    }

    public function setOptimizedProperties(array &$config = []): void
    {
        // Ensure we set all optimized values even if no value is received
        // when configuring the Metadata model. Configuration may happen on the field type
        foreach (array_keys($this->getAttributes()) as $key) {
            $setter = 'set' . ucfirst($key);
            $optimizedSettingValue = $config[$key] ?? null;
            if ($optimizedSettingValue) {
                $this->{$setter}($optimizedSettingValue);
            }

            unset($config[$key]);
        }
    }

    /**
     * Determines the schema settings from the Global and Element Metadata field settings
     */
    public function setSchemaProperties(): void
    {
        $identity = MetaModule::getInstance()->optimizeMetadata->globals['identity'] ?? null;
        $elementMetadataField = MetaModule::getInstance()->optimizeMetadata->elementMetadataField ?? null;

        $globalSchemaTypeId = null;
        $globalSchemaOverrideTypeId = null;
        $elementMetadataFieldSchemaTypeId = null;
        $elementMetadataFieldSchemaOverrideTypeId = null;

        if (isset($identity['@type']) && $identity['@type']) {
            $globalSchemaTypeId = $identity['@type'];
        }

        if (isset($identity['organizationSubTypes']) && (is_countable($identity['organizationSubTypes']) ? count($identity['organizationSubTypes']) : 0)) {
            $schemaSubTypes = array_filter($identity['organizationSubTypes']);
            // Get most specific override value
            $schemaOverrideTypeId = end($schemaSubTypes);
            $globalSchemaOverrideTypeId = $schemaOverrideTypeId;
        }

        if ($elementMetadataField !== null) {
            if (!empty($elementMetadataField->schemaTypeId)) {
                $elementMetadataFieldSchemaTypeId = $elementMetadataField->schemaTypeId;
            }

            if (!empty($elementMetadataField->schemaOverrideTypeId)) {
                $elementMetadataFieldSchemaOverrideTypeId = $elementMetadataField->schemaOverrideTypeId;
            }
        }

        $schemaTypeId = $elementMetadataFieldSchemaTypeId ?? $globalSchemaTypeId ?? null;
        $schemaOverrideTypeId = $elementMetadataFieldSchemaOverrideTypeId ?? $globalSchemaOverrideTypeId ?? null;

        $this->setSchemaTypeId($schemaTypeId);
        $this->setSchemaOverrideTypeId($schemaOverrideTypeId);
    }

    protected function setMetaTypes(array &$config = []): void
    {
        $metaTypes = [
            new SearchMetaType([], $this),
            new OpenGraphMetaType([], $this),
            new TwitterMetaType([], $this),
            new GeoMetaType([], $this),
            new RobotsMetaType([], $this),
        ];

        foreach ($metaTypes as $metaType) {
            $this->populateMetaType($config, $metaType);
        }
    }

    /**
     * @return MetaType[]
     */
    public function getMetaTypes(): array
    {
        return $this->metaTypes;
    }

    public function getMetaType(string $handle): ?MetaType
    {
        return $this->metaTypes[$handle] ?? null;
    }

    /**
     * Returns metadata as a flat array of the base values stored on the model.
     * The raw data is stored in the database and used when submitting related forms.
     * This method does not return any calculated values.
     *
     * We don't store optimized data here because it's dynamic and may rely on multiple
     * fields, and Craft only updates fields that have changed. Optimized metadata will be
     * generated dynamically on front-end requests.
     */
    public function getRawData(): array
    {
        $metaForDb = [];

        $metaForDb['canonical'] = $this->getCanonical();

        foreach ($this->metaTypes as $metaType) {
            $staticAttributes = $metaType->getRawData();

            foreach ($staticAttributes as $attribute) {
                $getter = 'get' . ucfirst($attribute);
                if (method_exists($metaType, $getter)) {
                    $value = $metaType->{$getter}();
                    $metaForDb[$attribute] = $value;
                }
            }
        }

        return $metaForDb;
    }

    /**
     * Returns the calculated values for the metadata used in the front-end meta tags.
     */
    public function getMetaTagData(): array
    {
        $metaTagData = [];

        foreach ($this->metaTypes as $metaType) {
            $metaTagByType = $metaType->getMetaTagData();

            // Remove blank or null values
            $metaTagData[$metaType->getHandle()] = array_filter($metaTagByType);
        }

        return $metaTagData;
    }

    protected function populateMetaType(array &$config, MetaType $metaType): void
    {
        // Match the values being populated to a given Meta Type model
        $metaAttributes = array_intersect_key($config, $metaType->getAttributes());

        foreach ($metaAttributes as $key => $value) {
            // Build the setter name dynamically: i.e. ogTitle => setOgTitle()
            $setter = 'set' . ucfirst($key);
            if ($value) {
                $metaType->{$setter}($value);
            }

            unset($config[$key]);
        }

        $this->metaTypes[$metaType->handle] = $metaType;
    }
}
