<?php

/** @noinspection DuplicatedCode DuplicatedCode */

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

                if ($isManualTitle) {
                    $settings['optimizedTitleField'] = null;
                }

                if ($isManualDescription) {
                    $settings['optimizedDescriptionField'] = null;
                }

                if ($isManualImage) {
                    $settings['optimizedImageField'] = null;
                }

                if ($isManualKeywords) {
                    $settings['optimizedKeywordsField'] = null;
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

        $isManualTitle = $settings['optimizedTitleField'] === 'manually';
        $isManualDescription = $settings['optimizedDescriptionField'] === 'manually';
        $isManualImage = $settings['optimizedImageField'] === 'manually';
        $isManualKeywords = $settings['optimizedKeywordsField'] === 'manually';

        $hasMetaDetails = isset($settings['enableMetaDetailsFields']) &&
            $settings['enableMetaDetailsFields'] === true;

        $isUsingSearchMetaDetails = $hasMetaDetails && $settings['showSearchMeta'] === true;
        $isUsingOpenGraphMetaDetails = $hasMetaDetails && $settings['showOpenGraph'] === true;
        $isUsingTwitterMetaDetails = $hasMetaDetails && $settings['showTwitter'] === true;

        foreach ($rows as $row) {
            $fieldData = Json::decode($row[$fieldColumn]);

            // If Editable Fields are the only thing in use (optimizedTitle, optimizedDescription, optimizedKeywords, optimizedImage)
                // Migrate Editable to Meta Details (title, description, keywords, ogImage, twitterImage)
            // If Editable + Meta Details are both in use
                // If Editable field override is null, use Meta Details value
                // If Meta Details is null, just use the Editable Field value

            if ($isManualTitle) {
                $fieldData['title'] = $fieldData['optimizedTitle'] ?? $fieldData['title'];
                $fieldData['optimizedTitle'] = null;
                $settings['optimizedTitleField'] = null;
            }
            if ($isManualDescription) {
                $fieldData['description'] = $fieldData['optimizedDescription'] ?? $fieldData['description'];
                $fieldData['optimizedDescription'] = null;
                $settings['optimizedDescriptionField'] = null;
            }
            if ($isManualKeywords) {
                $fieldData['keywords'] = $fieldData['optimizedKeywords'] ?? $fieldData['keywords'];
                $fieldData['optimizedKeywords'] = null;
                $settings['optimizedKeywordsField'] = null;
            }
            if ($isManualImage) {
                $fieldData['ogImage'] = $fieldData['optimizedImage'] ?? $fieldData['ogImage'];
                $fieldData['twitterImage'] = $fieldData['optimizedImage'] ?? $fieldData['twitterImage'];
                $fieldData['optimizedImage'] = null;
                $settings['optimizedImageField'] = null;
            }

            if (!empty($fieldData['robots'])) {
                $robotsValues = [
                    'noindex' => '0',
                    'nofollow' => '0',
                    'noarchive' => '0',
                    'noimageindex' => '0',
                    'noodp' => '0',
                    'noydir' => '0',
                ];

                $fieldData['robots'] = str_replace('"', '', $fieldData['robots']);
                $oldRobots = explode(',', $fieldData['robots']);
                foreach ($oldRobots as $robotValue) {
                    $robotsValues[trim($robotValue)] = '1';
                }

                $row['robots'] = $robotsValues;
            }

            $this->update('{{%content}}', [
                $fieldColumn => Json::encode($fieldData),
            ], [
                'id' => $row['id'],
            ]);
        }

        // At least one of these scenarios exists, so ensure Meta Detail Fields are enabled
        $settings['enableMetaDetailsFields'] = true;

        if (!$isUsingSearchMetaDetails) {
            $settings['showSearchMeta'] = true;
        }

        if (!$isUsingOpenGraphMetaDetails) {
            $settings['showOpenGraph'] = true;
        }

        if (!$isUsingTwitterMetaDetails) {
            $settings['showTwitter'] = true;
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
