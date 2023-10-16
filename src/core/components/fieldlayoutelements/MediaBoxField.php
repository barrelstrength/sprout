<?php

namespace BarrelStrength\Sprout\core\components\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldLayoutElement;

class MediaBoxField extends FieldLayoutElement
{
    public ?string $heading = null;

    public ?string $body = null;

    public ?string $addButtonText = null;

    public ?string $addButtonLink = null;

    public ?string $resourcePath = null;

    public function selectorHtml(): string
    {
        return 'Media Box';
    }

    public function formHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-core/_components/fieldlayoutelements/mediabox/mediabox.twig', [
            'heading' => $this->heading,
            'body' => $this->body,
            'addButtonText' => $this->addButtonText,
            'addButtonLink' => $this->addButtonLink,
            'resourcePath' => $this->resourcePath,
        ]);
    }
}
