<?php

namespace BarrelStrength\Sprout\core\components\linktypes;

use Craft;
use craft\fields\Link;
use craft\fields\linktypes\BaseLinkType;
use craft\fields\linktypes\BaseTextLinkType;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use craft\models\Site;
use yii\web\ForbiddenHttpException;

class CurrentUrl extends BaseTextLinkType
{
    public static function id(): string
    {
        return 'current-url';
    }

    public static function displayName(): string
    {
        return Craft::t('sprout-module-core', 'Current URL');
    }

    public function linkLabel(string $value): string
    {
        return $value;
    }

    public function supports(string $value): bool
    {
        return str_starts_with($value, '?');
    }

    public function validateValue(string $value, ?string &$error = null): bool
    {
        $site = Cp::requestedSite();

        if (!$site instanceof Site) {
            throw new ForbiddenHttpException('User not authorized to edit content in any sites.');
        }

        // This might not be the URL of the exact page but it should confirm that the appended value creates a valid URL
        $url = UrlHelper::siteUrl($value, null, null, $site->id);

        return filter_var($url, FILTER_VALIDATE_URL);
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
            'placeholder' => Craft::t('site', '?success=true'),
        ];

        return Html::textInput( 'value', $value, $textInputAttributes);
    }

    protected function pattern(): string
    {
        return '^?';
    }

    protected function urlPrefix(): string|array
    {
        return [
            '?',
        ];
    }
}
