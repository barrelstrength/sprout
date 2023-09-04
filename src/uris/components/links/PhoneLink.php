<?php

namespace BarrelStrength\Sprout\uris\components\links;

use BarrelStrength\Sprout\uris\links\AbstractLink;
use Craft;
use craft\helpers\Cp;

class PhoneLink extends AbstractLink
{
    public ?string $phone = null;

    public static function displayName(): string
    {
        return Craft::t('sprout-module-uris', 'Phone');
    }

    public function getInputHtml(): ?string
    {
        return Cp::textHtml([
            'name' => $this->namespaceInputName('phone'),
            'placeholder' => Craft::$app->getUser()->getIdentity()->email,
            'value' => $this->phone,
        ]);
    }

    public function getUrl(): ?string
    {
        return 'tel:' . $this->phone;
    }

    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['url'], 'url'];

        return $rules;
    }
}
