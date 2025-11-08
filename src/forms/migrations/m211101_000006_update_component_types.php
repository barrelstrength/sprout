<?php

namespace BarrelStrength\Sprout\forms\migrations;

use craft\db\Migration;

/**
 * @role temporary: Craft 4 => 5
 * @schema sprout-module-forms
 */
#[\Deprecated('tag:craftcms/cms:6.0', 'migration will be removed in Craft CMS 6.0')]
class m211101_000006_update_component_types extends Migration
{
    public function safeUp(): void
    {
        $components = [
            'sproutforms_integrations' => [
                'type' => [
                    [
                        'oldType' => 'barrelstrength\sproutforms\integrationtypes\EntryElementIntegration',
                        'newType' => 'BarrelStrength\Sprout\forms\components\integrationtypes\EntryElementIntegrationType',
                    ],
                    [
                        'oldType' => 'barrelstrength\sproutforms\integrationtypes\CustomEndpoint',
                        'newType' => 'BarrelStrength\Sprout\forms\components\integrationtypes\CustomEndpointIntegrationType',
                    ],
                ],
            ],
            // @todo - create these Form Types and insert UID
            'sproutforms_forms' => [
                'formTemplateId' => [
                    [
                        'oldType' => null,
                        'newType' => 'BarrelStrength\Sprout\forms\components\formtypes\DefaultFormType',
                    ],
                    [
                        'oldType' => 'barrelstrength\sproutforms\formtemplates\AccessibleTemplates',
                        'newType' => 'BarrelStrength\Sprout\forms\components\formtypes\DefaultFormType',
                    ],
                    [
                        // This value will not be found in the database and is handled in
                        // the next migration m211101_000007_migrate_forms_tables
                        'oldType' => 'barrelstrength\sproutforms\formtemplates\CustomTemplates',
                        'newType' => 'BarrelStrength\Sprout\forms\components\formtypes\CustomTemplatesFormType',
                    ],
                ],
            ],
        ];

        foreach ($components as $dbTableName => $columns) {
            foreach ($columns as $column => $types) {
                foreach ($types as $type) {
                    $dbTable = '{{%' . $dbTableName . '}}';

                    if (!$this->db->columnExists($dbTable, $column)) {
                        continue;
                    }

                    $this->update($dbTable, [
                        $column => $type['newType'],
                    ], [
                        $column => $type['oldType'],
                    ], [], false);
                }
            }
        }
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
