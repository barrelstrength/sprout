<?php

namespace BarrelStrength\Sprout\mailer\subscriberlists;

use BarrelStrength\Sprout\mailer\db\SproutTable;
use craft\db\ActiveRecord;
use craft\validators\UniqueValidator;

/**
 * @property int $id
 * @property int $subscriberListId
 * @property int $userId
 */
class SubscriptionRecord extends ActiveRecord
{
    public ?string $email = null;

    public static function tableName(): string
    {
        return SproutTable::SUBSCRIPTIONS;
    }

    public function rules(): array
    {
        $rules = parent::rules();

        $rules[] = [['subscriberListId', 'userId'], 'required'];
        $rules[] = [['email'], 'email'];
        $rules[] = [
            ['subscriberListId', 'userId'],
            UniqueValidator::class,
            'targetClass' => self::class,
            'targetAttribute' => ['subscriberListId', 'userId'],
        ];

        return $rules;
    }
}
