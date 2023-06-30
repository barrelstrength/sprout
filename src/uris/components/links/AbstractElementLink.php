<?php

namespace BarrelStrength\Sprout\uris\components\links;

use BarrelStrength\Sprout\uris\links\LinkInterface;
use BarrelStrength\Sprout\uris\links\UriLinkTrait;
use Craft;
use craft\base\ElementInterface;
use craft\helpers\Cp;

abstract class AbstractElementLink extends AbstractLink implements LinkInterface
{
    use UriLinkTrait;

    public mixed $elementId = null;

    abstract public static function elementType(): string;

    public static function displayName(): string
    {
        /** @var ElementInterface|string $elementType */
        $elementType = static::elementType();

        return $elementType::displayName();
    }

    public function getInputHtml(): ?string
    {
        if ($this->elementId) {
            $element = Craft::$app->getElements()->getElementById($this->elementId);
        } else {
            $element = null;
        }

        /** @var ElementInterface|string $elementType */
        $elementType = static::elementType();

        return Cp::elementSelectHtml([
            //'label' => $elementType::displayName(),
            'name' => static::class.'[elementId]',
            'elements' => $element ? [$element] : [],
            'elementType' => $elementType,
            'selectionLabel' => 'Choose a ' . $elementType::displayName(),
            'single' => true,
            //'sources' => $this->sources(),
            //'criteria' => $this->criteria(),
            //'condition' => $this->selectionCondition(),
        ]);
    }
    public function getUrl(): ?string
    {
        $element = Craft::$app->getElements()->getElementById($this->elementId);

        return $element->getUrl();
    }
}
