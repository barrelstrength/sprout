<?php

namespace BarrelStrength\Sprout\core\components\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseNativeField;
use craft\helpers\Template;

class RelationsTableField extends BaseNativeField
{
    public array $rows = [];

    public ?string $newButtonHtml = null;

    public ?string $sidebarHtml = null;

    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-core/_components/fieldlayoutelements/relationstable/body.twig', [
            'rows' => $this->rows,
            'newButtonHtml' => $this->newButtonHtml ? Template::raw($this->newButtonHtml) : null,
            'sidebarHtml' => $this->sidebarHtml ? Template::raw($this->sidebarHtml) : null,
        ]);
    }
}
