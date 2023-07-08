<?php

namespace BarrelStrength\Sprout\forms\migrations\helpers;

use craft\helpers\StringHelper;

class FormContentTableHelper
{
    public static function getContentTable(string $handle = null): ?string
    {
        if (!$handle) {
            return null;
        }

        $name = StringHelper::toLowerCase(trim($handle));

        return '{{%sprout_formcontent_' . $name . '}}';
    }

    /**
     * Creates the content table for a Form.
     */
    public static function createContentTable($tableName): void
    {
        $migration = new CreateFormContentTable([
            'tableName' => $tableName,
        ]);

        ob_start();
        $migration->up();
        ob_end_clean();
    }

    /**
     * Returns the content table name for a given form field
     */
    //public static function getContentTable(FormElement $form, bool $useOldHandle = false): bool|string
    //{
    //    if ($useOldHandle) {
    //        if (!$form->oldHandle) {
    //            return false;
    //        }
    //
    //        $handle = $form->oldHandle;
    //    } else {
    //        $handle = $form->handle;
    //    }
    //
    //
    //}
}
