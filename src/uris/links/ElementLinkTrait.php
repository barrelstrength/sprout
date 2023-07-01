<?php

namespace BarrelStrength\Sprout\uris\links;

use craft\base\ElementInterface;
use craft\helpers\Cp;
use Craft;

trait ElementLinkTrait
{
    public ?int $elementId = null;

    public static function displayName(): string
    {
        /** @var ElementInterface|string $elementType */
        $elementType = static::elementType();

        return $elementType::displayName();
    }

    public function getInputHtml(): ?string
    {
        $element = $this->elementId
            ? Craft::$app->getElements()->getElementById($this->elementId)
            : null;

        /** @var ElementInterface|string $elementType */
        $elementType = static::elementType();

        return Cp::elementSelectHtml([
            'name' => $this->namespaceInputName('elementId'),
            'elements' => $element ? [$element] : [],
            'elementType' => $elementType,
            'selectionLabel' => 'Choose a ' . $elementType::displayName(),
            'single' => true,
        ]);
    }

    public function getUrl(): ?string
    {
        $element = Craft::$app->getElements()->getElementById($this->elementId);

        if (!$element) {
            return null;
        }

        return $element->getUrl();
    }
}
