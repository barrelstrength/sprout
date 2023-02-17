<?php

namespace BarrelStrength\Sprout\mailer\subscriptions;

use craft\base\Model;
use craft\validators\UniqueValidator;
use DateTime;

class Subscription extends Model implements SubscriptionInterface
{
    public ?int $id = null;

    public ?string $listHandle = null;

    public ?int $listId = null;

    public ?int $elementId = null;

    public ?int $itemId = null;

    public ?string $email = null;

    public ?string $firstName = null;

    public ?string $lastName = null;

    public ?DateTime $dateCreated = null;

    public ?DateTime $dateUpdated = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [
            ['email'],
            'required',
            'on' => [self::SCENARIO_SUBSCRIBER],
        ];
        $rules[] = [
            ['listId'],
            'required',
            'when' => static function(): bool {
                return !self::SCENARIO_SUBSCRIBER;
            },
        ];
        $rules[] = [['email'], 'email'];
        $rules[] = [
            ['listId', 'itemId'],
            UniqueValidator::class,
            'targetClass' => SubscriptionRecord::class,
            'targetAttribute' => ['listId', 'itemId'],
        ];

        return $rules;
    }
}
