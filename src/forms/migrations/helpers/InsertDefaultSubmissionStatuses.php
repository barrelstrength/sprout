<?php

namespace BarrelStrength\Sprout\forms\migrations\helpers;

use BarrelStrength\Sprout\forms\db\SproutTable;
use Craft;
use craft\db\Migration;

class InsertDefaultSubmissionStatuses extends Migration
{
    public function safeUp(): void
    {
        $defaultSubmissionStatuses = [
            0 => [
                'name' => 'Unread',
                'handle' => 'unread',
                'color' => 'blue',
                'sortOrder' => 1,
                'isDefault' => 1,
            ],
            1 => [
                'name' => 'Read',
                'handle' => 'read',
                'color' => 'grey',
                'sortOrder' => 2,
                'isDefault' => 0,
            ],
            2 => [
                'name' => 'Spam',
                'handle' => 'spam',
                'color' => 'black',
                'sortOrder' => 3,
                'isDefault' => 0,
            ],
        ];

        foreach ($defaultSubmissionStatuses as $submissionStatus) {
            Craft::$app->getDb()->createCommand()->insert(SproutTable::FORM_SUBMISSIONS_STATUSES, [
                'name' => $submissionStatus['name'],
                'handle' => $submissionStatus['handle'],
                'color' => $submissionStatus['color'],
                'sortOrder' => $submissionStatus['sortOrder'],
                'isDefault' => $submissionStatus['isDefault'],
            ]);
        }
    }

    public function safeDown(): bool
    {
        return false;
    }
}
