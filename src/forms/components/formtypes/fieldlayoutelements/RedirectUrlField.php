<?php

namespace BarrelStrength\Sprout\forms\components\formtypes\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\TextField;
use craft\fields\data\LinkData;
use craft\fields\Link;

class RedirectUrlField extends TextField
{
    public string $attribute = 'redirectUrl';

    public ?string $name = 'formTypeSettings[redirectUrl]';

    protected function defaultLabel(ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('sprout-module-forms', 'Redirect URL');
    }

    protected function defaultInstructions(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('sprout-module-forms', 'Where should the user be redirected upon form submission?');
    }

    protected function selectorIcon(): ?string
    {
        return 'sign-post';
    }

    protected function value(?ElementInterface $element = null): ?LinkData
    {
        return $element?->getFormType()?->redirectUrl;
    }

    public static function getNewRedirectLinkField(): Link
    {
        return new Link([
            'types' => [
                'current-url',
                'relative-url',
                'absolute-url',
                'category',
                'entry',
            ],
        ]);
    }

    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        $link = self::getNewRedirectLinkField();
        $link->handle = 'formTypeSettings[' . $this->attribute . ']';

        $html = $link->getInputHtml($this->value($element), $element);

        return $html;
    }
}
