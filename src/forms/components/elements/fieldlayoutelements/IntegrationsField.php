<?php

namespace BarrelStrength\Sprout\forms\components\elements\fieldlayoutelements;

use BarrelStrength\Sprout\forms\components\elements\FormElement;
use BarrelStrength\Sprout\forms\FormsModule;
use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseNativeField;

class IntegrationsField extends BaseNativeField
{
    public bool $mandatory = true;

    public string $attribute = 'integrations-field';

    protected function useFieldset(): bool
    {
        return true;
    }

    protected function showLabel(): bool
    {
        return false;
    }

    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        if (!$element instanceof FormElement) {
            return '';
        }

        $integrations = FormsModule::getInstance()->formIntegrations->getIntegrationsByFormId($this->id);

        return Craft::$app->getView()->renderTemplate('sprout-module-forms/forms/_settings/integrations.twig', [
            'form' => $element,
            'integrations' => $integrations,
        ]);
    }
}
