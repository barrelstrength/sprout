<?php

namespace BarrelStrength\Sprout\meta\migrations;

use BarrelStrength\Sprout\meta\components\fields\ElementMetadataField;
use Craft;
use craft\db\Migration;
use craft\elements\Entry;
use craft\queue\jobs\ResaveElements;

class m250309_000000_resave_sections_with_element_metadata_field extends Migration
{
    public function safeUp(): void
    {
        $sectionHandlesWithMetadataFields = [];

        $sections = Craft::$app->getSections()->getAllSections();
        foreach ($sections as $section) {
            $entryTypes = $section->getEntryTypes();
            foreach ($entryTypes as $entryType) {
                $fieldLayout = $entryType->getFieldLayout();
                $fields = $fieldLayout->getCustomFields();
                foreach ($fields as $field) {
                    if ($field instanceof ElementMetadataField) {
                        $sectionHandlesWithMetadataFields[] = $section->handle;
                    }
                }
            }
        }

        $sectionHandlesWithMetadataFields = array_unique($sectionHandlesWithMetadataFields);

        if (count($sectionHandlesWithMetadataFields) > 0) {
            Craft::$app->getQueue()->push(new ResaveElements([
                'elementType' => Entry::class,
                'criteria' => [
                    'section' => $sectionHandlesWithMetadataFields,
                ],
            ]));
        }
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
