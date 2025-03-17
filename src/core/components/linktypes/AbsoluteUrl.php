<?php

namespace BarrelStrength\Sprout\core\components\linktypes;

use Craft;
use craft\fields\Link;
use craft\fields\linktypes\Url;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\models\Site;
use yii\web\ForbiddenHttpException;

class AbsoluteUrl extends Url
{
    public static function id(): string
    {
        return 'absolute-url';
    }

    public static function displayName(): string
    {
        return Craft::t('sprout-module-core', 'Absolute URL');
    }

    public function linkLabel(string $value): string
    {
        return $value;
    }

    public function validateValue(string $value, ?string &$error = null): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL);
    }

    public function supports(string $value): bool
    {
        return str_starts_with($value, 'http');
    }

    protected function urlPrefix(): array
    {
        $site = Cp::requestedSite();

        if (!$site instanceof Site) {
            throw new ForbiddenHttpException('User not authorized to edit content in any sites.');
        }

        return [
            $site->getBaseUrl(),
            //'/',
            //'',
        ];
    }

    protected function pattern(): string
    {
        return '^http';
    }

    public function inputHtml(Link $field, ?string $value, string $containerId): string
    {
        $site = Cp::requestedSite();

        if (!$site instanceof Site) {
            throw new ForbiddenHttpException('User not authorized to edit content in any sites.');
        }

        $textInputAttributes = [
            'describedBy' => $field->describedBy,
            'class' => ['fullwidth', 'text-link-input', 'text'],
            'inputAttributes' => [
                'aria' => [
                    'label' => Craft::t('site', $field->name),
                ],
            ],
            'placeholder' => Craft::t('site', $site->getBaseUrl()),
        ];

        return Html::textInput( 'value', $value, $textInputAttributes);
    }
}
