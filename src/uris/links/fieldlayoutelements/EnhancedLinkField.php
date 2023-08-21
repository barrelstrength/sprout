<?php

namespace BarrelStrength\Sprout\uris\links\fieldlayoutelements;

use BarrelStrength\Sprout\core\helpers\ComponentHelper;
use BarrelStrength\Sprout\core\twig\TemplateHelper;
use BarrelStrength\Sprout\uris\UrisModule;
use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseNativeField;

class EnhancedLinkField extends BaseNativeField
{
    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        $linksService = UrisModule::getInstance()->links;
        $linkTypes = $linksService->getLinkTypes();

        return Craft::$app->getView()->renderTemplate('sprout-module-uris/links/input.twig', [
            'id' => $this->id(),
            'describedBy' => $this->describedBy($element, $static),
            'name' => $this->name ?? $this->attribute(),
            'fieldNamespace' => $this->name ?? $this->attribute(),
            'selectedLink' => $this->value($element),
            'linkOptions' => TemplateHelper::optionsFromComponentTypes($linkTypes),
            'links' => ComponentHelper::typesToInstances($linkTypes),
            'type' => $this->value($element),
        ]);
    }
}
