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
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\AddressFormField',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\Categories',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\CategoriesFormField',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\Checkboxes',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\CheckboxesFormField',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\CustomHtml',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\CustomHtmlFormField',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\Date',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\DateFormField',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\Dropdown',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\DropdownFormField',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\Email',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\EmailFormField',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\EmailDropdown',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\EmailDropdownFormField',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\Entries',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\EntriesFormField',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\FileUpload',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\FileUploadFormField',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\Hidden',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\HiddenFormField',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\Invisible',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\InvisibleFormField',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\MultipleChoice',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\MultipleChoiceFormField',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\MultiSelect',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\MultiSelectFormField',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\Name',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\NameFormField',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\Number',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\NumberFormField',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\OptIn',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\OptInFormField',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\Paragraph',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\ParagraphFormField',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\Phone',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\PhoneFormField',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\PrivateNotes',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\PrivateNotesFormField',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\RegularExpression',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\RegularExpressionFormField',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\SectionHeading',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\SectionHeadingFormField',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\SingleLine',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\SingleLineFormField',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\Tags',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\TagsFormField',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\Url',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\UrlFormField',
            ],
            [
                'oldType' => 'barrelstrength\sproutforms\fields\formfields\Users',
                'newType' => 'BarrelStrength\Sprout\forms\components\formfields\UsersFormField',
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
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
