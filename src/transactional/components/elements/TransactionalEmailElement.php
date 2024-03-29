<?php

namespace BarrelStrength\Sprout\transactional\components\elements;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\transactional\components\elements\conditions\TransactionalEmailCondition;
use Craft;
use craft\elements\conditions\ElementConditionInterface;

class TransactionalEmailElement extends EmailElement
{
    public static function displayName(): string
    {
        return Craft::t('sprout-module-transactional', 'Transactional Email');
    }

    public static function lowerDisplayName(): string
    {
        return Craft::t('sprout-module-transactional', 'transactional email');
    }

    public static function pluralDisplayName(): string
    {
        return Craft::t('sprout-module-transactional', 'Transactional Emails');
    }

    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('sprout-module-transactional', 'transactional emails');
    }

    public static function createCondition(): ElementConditionInterface
    {
        return Craft::createObject(TransactionalEmailCondition::class, [static::class]);
    }

    public static function find(): TransactionalEmailElementQuery
    {
        return new TransactionalEmailElementQuery(EmailElement::class);
    }

    protected static function defineSources(string $context = null): array
    {
        return [
            [
                'key' => '*',
                'label' => Craft::t('sprout-module-transactional', 'All transactional emails'),
            ],
        ];
    }
}
