<?php

namespace BarrelStrength\Sprout\meta\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\ElementHelper;
use craft\helpers\Json;

class m211101_000002_update_field_settings extends Migration
{
    public function safeUp(): void
    {
        $fields = (new Query())
            ->select(['id', 'handle', 'columnSuffix', 'settings'])
            ->from('{{%fields}}')
            ->where(['type' => 'barrelstrength\sproutseo\fields\ElementMetadata'])
            ->all();

        // Remove deprecated attributes and resave settings
        foreach ($fields as $field) {
            $id = $field['id'];
            $settings = Json::decode($field['settings']);

            // Migrate Editable Fields for Title, Description, and Keywords..?

            $isManualTitle = $settings['optimizedTitleField'] === 'manually';
            $isManualDescription = $settings['optimizedDescriptionField'] === 'manually';
            $isManualImage = $settings['optimizedImageField'] === 'manually';
            $isManualKeywords = $settings['optimizedKeywordsField'] === 'manually';

            $isUsingEditableFields = $isManualTitle || $isManualDescription || $isManualImage || $isManualKeywords;

            // If editable fields are in use, we need to migrate any data stored in the content table
            // in the Editable field attributes to the Meta Details field attributes
            if ($isUsingEditableFields) {
                $fieldColumn = ElementHelper::fieldColumn('field_', $field['handle'], $field['columnSuffix']);

                if ($this->db->columnExists('{{%content}}', $fieldColumn)) {
                    $settings = $this->migrateContentData($fieldColumn, $settings);
                }
            }

            unset(
                // Changed
                $settings['enableMetaDetailsSearch'],
                $settings['enableMetaDetailsOpenGraph'],
                $settings['enableMetaDetailsTwitterCard'],
                $settings['enableMetaDetailsGeo'],
                $settings['enableMetaDetailsRobots'],
                $settings['dateCreated'],
                $settings['dateUpdated'],
                $settings['uid'],
                $settings['elementId'],

                // Legacy attributes that might exist in an outlier
                $settings['metadata'],
                $settings['values'],
                $settings['showMainEntity'],

                // Migration m190913_152146_update_preview_targets triggered error looking for this
                $settings['meta'],
            );

            if (isset($settings['schemaTypeId'])) {
                $schemaTypeId = $settings['schemaTypeId'];
                $schemaMapping = $this->getSchemaMapping();

                if (array_key_exists($schemaTypeId, $schemaMapping)) {
                    // Update schemaTypeId to new namespace
                    $settings['schemaTypeId'] = $schemaMapping[$schemaTypeId];
                }
            }

            $this->update(Table::FIELDS, [
                'settings' => Json::encode($settings),
            ], ['id' => $id], [], false);
        }
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }

    private function migrateContentData(string $fieldColumn, mixed $settings): array
    {
        $rows = (new Query())
            ->select(['id', $fieldColumn])
            ->from('{{%content}}')
            ->where(['not', [$fieldColumn => null]])
            ->all();

        $hasMetaDetails = isset($settings['enableMetaDetailsFields']) &&
            $settings['enableMetaDetailsFields'] === true;

        $isUsingSearchMetaDetails = $hasMetaDetails && $settings['showSearchMeta'] === true;
        $isUsingOpenGraphMetaDetails = $hasMetaDetails && $settings['showOpenGraph'] === true;
        $isUsingTwitterMetaDetails = $hasMetaDetails && $settings['showTwitter'] === true;

        foreach ($rows as $row) {
            $data = Json::decode($row[$fieldColumn]);

            if ($isUsingSearchMetaDetails || $isUsingOpenGraphMetaDetails || $isUsingTwitterMetaDetails) {
                // If Editable + Meta Details are both in use

                // If override is null, use Editable field
                // If Meta Details is not null, just use the Editable Field value
                $data['title'] = $data['optimizedTitle'] ?? $data['title'];
                $data['description'] = $data['optimizedDescription'] ?? $data['description'];
                $data['keywords'] = $data['optimizedKeywords'] ?? $data['keywords'];

                $data['ogImage'] = $data['optimizedImage'] ?? $data['ogImage'];
                $data['twitterImage'] = $data['optimizedImage'] ?? $data['twitterImage'];

                $this->update('{{%content}}', [
                    $fieldColumn => Json::encode($settings),
                ], [
                    'id' => $row['id'],
                ]);
            } else {
                // If Editable Fields are the only thing in use

                // Migrate Editable to Meta Details
                $data['title'] = $data['optimizedTitle'] ?? '';
                $data['description'] = $data['optimizedDescription'] ?? '';
                $data['keywords'] = $data['optimizedKeywords'] ?? '';

                $data['ogImage'] = $data['optimizedImage'] ?? '';
                $data['twitterImage'] = $data['optimizedImage'] ?? '';

                $this->update('{{%content}}', [
                    $fieldColumn => Json::encode($data),
                ], [
                    'id' => $row['id'],
                ]);
            }
        }

        if (!$isUsingSearchMetaDetails) {
            // If Editable Fields are the only thing in use
            // Update the settings to enable Search Meta Detail Fields
            $settings['enableMetaDetailsFields'] = true;
            $settings['showSearchMeta'] = true;
        }

        return $settings;
    }

    private function getSchemaMapping(): array
    {
        return [
            'barrelstrength\sproutseo\schema\ContactPointSchema' => 'BarrelStrength\Sprout\meta\components\schema\ContactPointSchema',
            'barrelstrength\sproutseo\schema\CreativeWorkSchema' => 'BarrelStrength\Sprout\meta\components\schema\CreativeWorkSchema',
            'barrelstrength\sproutseo\schema\EventSchema' => 'BarrelStrength\Sprout\meta\components\schema\EventSchema',
            'barrelstrength\sproutseo\schema\GeoSchema' => 'BarrelStrength\Sprout\meta\components\schema\GeoSchema',
            'barrelstrength\sproutseo\schema\ImageObjectSchema' => 'BarrelStrength\Sprout\meta\components\schema\ImageObjectSchema',
            'barrelstrength\sproutseo\schema\IntangibleSchema' => 'BarrelStrength\Sprout\meta\components\schema\IntangibleSchema',
            'barrelstrength\sproutseo\schema\MainEntityOfPageSchema' => 'BarrelStrength\Sprout\meta\components\schema\MainEntityOfPageSchema',
            'barrelstrength\sproutseo\schema\OrganizationSchema' => 'BarrelStrength\Sprout\meta\components\schema\OrganizationSchema',
            'barrelstrength\sproutseo\schema\PersonSchema' => 'BarrelStrength\Sprout\meta\components\schema\PersonSchema',
            'barrelstrength\sproutseo\schema\PlaceSchema' => 'BarrelStrength\Sprout\meta\components\schema\PlaceSchema',
            'barrelstrength\sproutseo\schema\PostalAddressSchema' => 'BarrelStrength\Sprout\meta\components\schema\PostalAddressSchema',
            'barrelstrength\sproutseo\schema\ProductSchema' => 'BarrelStrength\Sprout\meta\components\schema\ProductSchema',
            'barrelstrength\sproutseo\schema\ThingSchema' => 'BarrelStrength\Sprout\meta\components\schema\ThingSchema',
            'barrelstrength\sproutseo\schema\WebsiteIdentityOrganizationSchema' => 'BarrelStrength\Sprout\meta\components\schema\WebsiteIdentityOrganizationSchema',
            'barrelstrength\sproutseo\schema\WebsiteIdentityPersonSchema' => 'BarrelStrength\Sprout\meta\components\schema\WebsiteIdentityPersonSchema',
            'barrelstrength\sproutseo\schema\WebsiteIdentityPlaceSchema' => 'BarrelStrength\Sprout\meta\components\schema\WebsiteIdentityPlaceSchema',
            'barrelstrength\sproutseo\schema\WebsiteIdentityWebsiteSchema' => 'BarrelStrength\Sprout\meta\components\schema\WebsiteIdentityWebsiteSchema',
        ];
    }
}
