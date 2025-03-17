<?php

namespace BarrelStrength\Sprout\forms\components\formtypes\fieldlayoutelements;

use BarrelStrength\Sprout\forms\components\elements\FormElement;
use BarrelStrength\Sprout\forms\formtypes\LinkFieldLayoutFieldHelper;
use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\TextField;
use craft\fields\data\LinkData;
use craft\fields\Link;

class RedirectUrlField extends TextField
{
    public string $attribute = 'redirectUrl';

    public ?string $name = 'formTypeSettings[redirectUrl]';

    public ?LinkData $linkData = null;

    public static function getNewRedirectLinkField(array $settings = []): Link
    {
        return new Link(array_merge([
            'types' => [
                'current-url',
                'relative-url',
                'absolute-url',
                'category',
                'entry',
            ],
        ], $settings));
    }

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
        if (!$element instanceof FormElement) {
            return null;
        }

        if (!$this->linkData) {
            $config = $element->formTypeSettings[$this->attribute] ?? [];
            $linkData = LinkFieldLayoutFieldHelper::toFieldLayoutField($config);
            $this->linkData = $linkData;
        }

        return $this->linkData;
    }

    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        $link = self::getNewRedirectLinkField();
        $link->handle = 'formTypeSettings[' . $this->attribute . ']';

        $html = $link->getInputHtml($this->value($element), $element);

        return $html;
    }
}
