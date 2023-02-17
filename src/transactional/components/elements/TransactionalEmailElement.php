<?php

namespace BarrelStrength\Sprout\transactional\components\elements;

use BarrelStrength\Sprout\core\Sprout;
use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\components\elements\email\EmailElementQuery;
use BarrelStrength\Sprout\transactional\components\emailtypes\TransactionalEmailEmailType;
use Craft;
use craft\elements\db\ElementQueryInterface;

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

    public static function refHandle(): ?string
    {
        return 'transactional-email';
    }

    public static function find(): EmailElementQuery
    {
        return EmailElement::find()
            ->where([
                'emailType' => TransactionalEmailEmailType::class,
            ]);
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

    public static function indexHtml(ElementQueryInterface $elementQuery, ?array $disabledElementIds, array $viewState, ?string $sourceKey, ?string $context, bool $includeContainer, bool $showCheckboxes): string
    {
        Sprout::getInstance()->vite->register('mailer/SendEmailModal.js', false);

        return parent::indexHtml($elementQuery, $disabledElementIds, $viewState, $sourceKey, $context, $includeContainer, $showCheckboxes);
    }
}
