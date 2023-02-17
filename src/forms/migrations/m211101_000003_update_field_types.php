<?php

namespace BarrelStrength\Sprout\forms\migrations;

use craft\db\Migration;

class m211101_000003_update_field_types extends Migration
{
    public function safeUp(): void
    {
        $types = [
            // Forms
            [
                'oldType' => 'barrelstrength\sproutforms\fields\Forms',
                'newType' => 'BarrelStrength\Sprout\forms\components\fields\FormsRelationField',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\Entries',
                'newType' => 'BarrelStrength\Sprout\forms\components\fields\SubmissionsRelationField',
            ],

            // Form Fields
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\Address',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\Address',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\Categories',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\Categories',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\Checkboxes',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\Checkboxes',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\CustomHtml',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\CustomHtml',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\Date',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\Date',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\Dropdown',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\Dropdown',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\Email',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\Email',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\EmailDropdown',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\EmailDropdown',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\Entries',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\Entries',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\FileUpload',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\FileUpload',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\Hidden',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\Hidden',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\Invisible',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\Invisible',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\MultipleChoice',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\MultipleChoice',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\MultiSelect',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\MultiSelect',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\Name',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\Name',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\Number',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\Number',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\OptIn',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\OptIn',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\Paragraph',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\Paragraph',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\Phone',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\Phone',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\PrivateNotes',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\PrivateNotes',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\RegularExpression',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\RegularExpression',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\SectionHeading',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\SectionHeading',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\SingleLine',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\SingleLine',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\Tags',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\Tags',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\Url',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\Url',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\Users',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\Users',
            ],
        ];

        foreach ($types as $type) {
            $this->update('{{%fields}}', [
                'type' => $type['newType'],
            ], ['type' => $type['oldType']], [], false);
        }

        $this->fieldsModuleMigrations();
    }

    public function fieldsModuleMigrations(): void
    {
        $types = [
            [
                // @todo Migrate to Craft Address Field
                'oldType' => 'barrelstrength\sproutfields\fields\Address',
                'newType' => 'BarrelStrength\Sprout\components\fields\Address',
            ],
            [
                // @todo Migrate to Craft Email Field
                'oldType' => 'barrelstrength\sproutfields\fields\Email',
                'newType' => 'BarrelStrength\Sprout\components\fields\Email',
            ],
            [
                'oldType' => 'barrelstrength\sproutfields\fields\Gender',
                'newType' => 'BarrelStrength\Sprout\components\fields\Gender',
            ],
            [
                'oldType' => 'barrelstrength\sproutfields\fields\Name',
                'newType' => 'BarrelStrength\Sprout\components\fields\Name',
            ],
            [
                'oldType' => 'barrelstrength\sproutfields\fields\Phone',
                'newType' => 'BarrelStrength\Sprout\components\fields\Phone',
            ],
            //            [
            //                'oldType' => 'barrelstrength\sproutfields\fields\Predefined',
            //                'newType' => 'BarrelStrength\Sprout\components\fields\Predefined',
            //            ],
            //            [
            //                'oldType' => 'barrelstrength\sproutfields\fields\PredefinedDate',
            //                'newType' => 'BarrelStrength\Sprout\components\fields\PredefinedDate',
            //            ],
            [
                'oldType' => 'barrelstrength\sproutfields\fields\RegularExpression',
                'newType' => 'BarrelStrength\Sprout\components\fields\RegularExpression',
            ],
            [
                'oldType' => 'barrelstrength\sproutfields\fields\Template',
                'newType' => 'BarrelStrength\Sprout\components\fields\Template',
            ],
            [
                // @todo Migrate to Craft Url Field
                'oldType' => 'barrelstrength\sproutfields\fields\Url',
                'newType' => 'BarrelStrength\Sprout\components\fields\Url',
            ],
        ];

        foreach ($types as $type) {
            $this->update('{{%fields}}', [
                'type' => $type['newType'],
            ], ['type' => $type['oldType']], [], false);
        }
    }

    public function safeDown(): bool
    {
        echo "m211101_000003_update_field_types cannot be reverted.\n";

        return false;
    }
}
