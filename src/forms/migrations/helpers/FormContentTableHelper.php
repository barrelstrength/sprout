<?php

namespace BarrelStrength\Sprout\forms\migrations\helpers;

class FormContentTableHelper
{
    public static function getContentTable(string $id): ?string
    {
        return '{{%sprout_formcontent_' . $id . '}}';
    }

    public static function createContentTable($tableName): void
    {
        $migration = new CreateFormContentTable([
            'tableName' => $tableName,
        ]);

        ob_start();
        $migration->up();
        ob_end_clean();
    }
}
