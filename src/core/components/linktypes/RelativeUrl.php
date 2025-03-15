<?php

namespace BarrelStrength\Sprout\core\components\linktypes;

use Craft;
use craft\fields\Link;
use craft\fields\linktypes\BaseLinkType;
use craft\fields\linktypes\BaseTextLinkType;
use craft\fields\linktypes\Url;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\models\Site;
use yii\web\ForbiddenHttpException;

class RelativeUrl extends Url
{
    public static function id(): string
    {
        return 'relative-url';
    }

    public static function displayName(): string
    {
        return Craft::t('sprout-module-core', 'Relative URL');
    }

    public function linkLabel(string $value): string
    {
        return $value;
    }

    public function validateValue(string $value, ?string &$error = null): bool
    {
        // strip off any starting '/'
        //$value = ltrim($value, '/');

        return !(str_starts_with($value, 'http') || str_starts_with($value, '/'));
    }

    public function supports(string $value): bool
    {
        return str_starts_with($value, '/');
    }

    protected function urlPrefix(): array
    {
        $site = Cp::requestedSite();

        if (!$site instanceof Site) {
            throw new ForbiddenHttpException('User not authorized to edit content in any sites.');
        }

        return [
            //$site->getBaseUrl(),
            '/',
            '',
        ];
    }

    protected function pattern(): string
    {
        return '^\/';
    }

    public function inputHtml(Link $field, ?string $value, string $containerId): string
    {
        $textInputAttributes = [
            'describedBy' => $field->describedBy,
            'class' => ['fullwidth', 'text-link-input', 'text'],
            'inputAttributes' => [
                'aria' => [
                    'label' => Craft::t('site', $field->name),
                ],
            ],
            'placeholder' => Craft::t('site', '/thank-you?success=true'),
        ];

        return Html::textInput( 'value', $value, $textInputAttributes);
    }
}
