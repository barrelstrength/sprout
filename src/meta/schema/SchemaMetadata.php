<?php

namespace BarrelStrength\Sprout\meta\schema;

use BarrelStrength\Sprout\meta\components\schema\ContactPointSchema;
use BarrelStrength\Sprout\meta\components\schema\CreativeWorkSchema;
use BarrelStrength\Sprout\meta\components\schema\EventSchema;
use BarrelStrength\Sprout\meta\components\schema\GeoSchema;
use BarrelStrength\Sprout\meta\components\schema\ImageObjectSchema;
use BarrelStrength\Sprout\meta\components\schema\IntangibleSchema;
use BarrelStrength\Sprout\meta\components\schema\MainEntityOfPageSchema;
use BarrelStrength\Sprout\meta\components\schema\OrganizationSchema;
use BarrelStrength\Sprout\meta\components\schema\PersonSchema;
use BarrelStrength\Sprout\meta\components\schema\PlaceSchema;
use BarrelStrength\Sprout\meta\components\schema\PostalAddressSchema;
use BarrelStrength\Sprout\meta\components\schema\ProductSchema;
use BarrelStrength\Sprout\meta\components\schema\ThingSchema;
use BarrelStrength\Sprout\meta\components\schema\WebsiteIdentityOrganizationSchema;
use BarrelStrength\Sprout\meta\components\schema\WebsiteIdentityPersonSchema;
use BarrelStrength\Sprout\meta\components\schema\WebsiteIdentityPlaceSchema;
use BarrelStrength\Sprout\meta\components\schema\WebsiteIdentityWebsiteSchema;
use BarrelStrength\Sprout\meta\MetaModule;
use Craft;
use craft\events\RegisterComponentTypesEvent;
use craft\helpers\Json;
use yii\base\Component;

class SchemaMetadata extends Component
{
    /**
     * Only to be used by Sprout
     */
    public const INTERNAL_SPROUT_EVENT_REGISTER_SCHEMAS = 'registerInternalSproutSchemas';

    public const EVENT_REGISTER_SCHEMAS = 'registerSproutSchemas';

    /**
     * Full schema.org core and extended vocabulary as described on schema.org
     * http://schema.org/docs/full.html
     */
    public array $vocabularies = [];

    /**
     * All registered Schema Types
     */
    protected array $schemaTypes = [];

    /**
     * All instantiated Schema Types indexed by class
     */
    protected array $schemas = [];

    public function getSchemasTypes(): array
    {
        $schemas = [
            WebsiteIdentityOrganizationSchema::class,
            WebsiteIdentityPersonSchema::class,
            WebsiteIdentityWebsiteSchema::class,
            WebsiteIdentityPlaceSchema::class,
            ContactPointSchema::class,
            ImageObjectSchema::class,
            MainEntityOfPageSchema::class,
            PostalAddressSchema::class,
            GeoSchema::class,
            ThingSchema::class,
            CreativeWorkSchema::class,
            EventSchema::class,
            IntangibleSchema::class,
            OrganizationSchema::class,
            PersonSchema::class,
            PlaceSchema::class,
        ];

        if (Craft::$app->getPlugins()->isPluginInstalled('commerce')) {
            $schemas[] = ProductSchema::class;
        }

        $internalEvent = new RegisterComponentTypesEvent([
            'types' => $schemas,
        ]);

        $this->trigger(self::INTERNAL_SPROUT_EVENT_REGISTER_SCHEMAS, $internalEvent);

        $proEvent = new RegisterComponentTypesEvent([
            'types' => $schemas,
        ]);

        $this->trigger(self::EVENT_REGISTER_SCHEMAS, $proEvent);

        $availableSchemas = MetaModule::isPro()
            ? array_merge($internalEvent->types, $proEvent->types)
            : $internalEvent->types;

        foreach ($availableSchemas as $schema) {
            $this->schemaTypes[] = $schema;
        }

        return $this->schemaTypes;
    }

    /**
     * @return Schema[]
     */
    public function getSchemas(): array
    {
        $schemaTypes = $this->getSchemasTypes();

        foreach ($schemaTypes as $schemaClass) {
            $schema = new $schemaClass();
            $this->schemas[$schemaClass] = $schema;
        }

        uasort($this->schemas, static function($a, $b): int {
            /**
             * @var Schema $a
             * @var Schema $b
             */
            return $a->getName() <=> $b->getName();
        });

        return $this->schemas;
    }

    /**
     * Returns a list of available schema maps for display in a Main Entity select field
     */
    public function getSchemaOptions(): array
    {
        $schemas = $this->getSchemas();

        foreach ($schemas as $schemaClass => $schema) {
            if ($schema->isUnlistedSchemaType()) {
                unset($schemas[$schemaClass]);
            }
        }

        // Get a filtered list of our default Sprout Meta schema
        $defaultSchema = array_filter($schemas, static function($map): bool {
            /**
             * @var SchemaMetadata $map
             */
            return stripos($map::class, 'Barrelstrength\\Sprout\\meta\\components\\schema') !== false;
        });

        // Get a filtered list of of any custom schema
        $customSchema = array_filter($schemas, static function($map): bool {
            /**
             * @var SchemaMetadata $map
             */
            return stripos($map::class, 'Barrelstrength\\Sprout\\meta\\components\\schema') === false;
        });

        // Build our options
        $schemaOptions = [
            '' => Craft::t('sprout-module-meta', 'None'), [
                'optgroup' => Craft::t('sprout-module-meta', 'Default Types'),
            ],
        ];

        $schemaOptions = array_merge($schemaOptions, array_map(static function($schema): array {
            /**
             * @var Schema $schema
             */
            return [
                'label' => $schema->getName(),
                'type' => $schema->getType(),
                'value' => $schema::class,
            ];
        }, $defaultSchema));

        if ($customSchema !== []) {
            $schemaOptions[] = ['optgroup' => Craft::t('sprout-module-meta', 'Custom Types')];

            $schemaOptions = array_merge($schemaOptions, array_map(static function($schema): array {
                /**
                 * @var Schema $schema
                 */
                return [
                    'label' => $schema->getName(),
                    'type' => $schema->getType(),
                    'value' => $schema::class,
                    'isCustom' => '1',
                ];
            }, $customSchema));
        }

        return $schemaOptions;
    }

    /**
     * Prepare an array of the optimized Meta
     *
     * @param mixed[] $schemas
     *
     * @return array[][]
     */
    public function getSchemaSubtypes(array $schemas): array
    {
        $values = [];

        foreach ($schemas as $schema) {
            if (isset($schema['type'])) {
                $type = $schema['type'];

                // Create a generic first item in our list that matches the top level schema
                // We do this so we don't have a blank dropdown option for our secondary schemas
                //                $firstItem = [
                //                    $type => [],
                //                ];

                if (!isset($schema['isCustom'])) {
                    $schemaItems = $this->getSchemaChildren($type);

                    if ($schemaItems !== []) {
                        $values[$schema['value']] = [$schemaItems];
                    } else {
                        $values[$schema['value']] = [];
                    }
                }
            }
        }

        return $values;
    }

    /**
     * Returns a schema map instance (based on $uniqueKey) or $default
     */
    public function getSchemaByUniqueKey(string $uniqueKey, $default = null): ?Schema
    {
        $this->getSchemas();

        return array_key_exists($uniqueKey, $this->schemas) ? $this->schemas[$uniqueKey] : $default;
    }

    /**
     * Returns an array of vocabularies based on the path provided
     * MetaModule::getInstance()->schema->getVocabularies('Organization.LocalBusiness.AutomotiveBusiness');
     */
    public function getVocabularies($path = null): array
    {
        $jsonLdTreePath = Craft::getAlias('@BarrelStrength/Sprout/meta/schema/jsonld/tree.jsonld');

        $allVocabularies = Json::decode(file_get_contents($jsonLdTreePath));

        $this->vocabularies = $this->updateArrayKeys($allVocabularies['children'], 'name');

        if ($path) {
            return $this->getArrayByPath($this->vocabularies, $path);
        }

        return $this->vocabularies;
    }

    protected function getArrayByPath($array, $path, string $separator = '.')
    {
        $keys = explode($separator, $path);

        $level = 1;
        foreach ($keys as $key) {
            $array = $level == 1 ? $array[$key] : $array['children'][$key];

            $level++;
        }

        return $array;
    }

    protected function updateArrayKeys(array $oldArray, $replaceKey): array
    {
        $newArray = [];

        foreach ($oldArray as $key => $value) {
            if (isset($value[$replaceKey])) {
                $key = $value[$replaceKey];
            }

            if (is_array($value)) {
                $value = $this->updateArrayKeys($value, $replaceKey);
            }

            $newArray[$key] = $value;
        }

        return $newArray;
    }

    private function getSchemaChildren($type): array
    {
        $tree = MetaModule::getInstance()->schemaMetadata->getVocabularies($type);

        /**  @var array $children */
        $children = $tree['children'] ?? [];

        // let's assume 3 levels
        foreach ($children as $key => $level1) {
            $children[$key] = [];

            /** @var array $level1children */
            $level1children = $level1['children'] ?? [];

            foreach ($level1children as $key2 => $level2) {
                $children[$key][$key2] = [];

                /** @var array $level2children */
                $level2children = $level2['children'] ?? [];

                if ($level2children !== []) {
                    foreach (array_keys($level2children) as $key3) {
                        $children[$key][$key2][] = $key3;
                    }
                }
            }
        }

        return $children;
    }
}
