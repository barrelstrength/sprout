<?php

namespace BarrelStrength\Sprout\uris\components\links;

use BarrelStrength\Sprout\uris\links\AbstractLink;
use BarrelStrength\Sprout\uris\links\DataLinkTrait;
use Craft;
use craft\helpers\Cp;

class EmailLink extends AbstractLink
{
    public ?string $email = null;

    public static function displayName(): string
    {
        return Craft::t('sprout-module-uris', 'Email');
    }

    public function getInputHtml(): ?string
    {
        return Cp::textHtml([
            'name' => $this->namespaceInputName('email'),
            'placeholder' => Craft::$app->getUser()->getIdentity()->email,
            'value' => $this->email,
            'errors' => '',
        ]);
    }

    public function getUrl(): ?string
    {
        return 'mailto:'.$this->email;
    }

    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['email'], 'email'];

        return $rules;
    }
}
