<?php

namespace BarrelStrength\Sprout\core\sourcegroups;

trait SourceGroupTrait
{
    public ?int $groupId = null;

    /**
     * Returns all Source Groups for a given Source Group Record
     */
    public static function getSourceGroups(): array
    {
        return SourceGroupRecord::find()
            ->where(['type' => static::class])
            ->orderBy(['name' => SORT_ASC])
            ->indexBy('id')
            ->all();
    }
}
