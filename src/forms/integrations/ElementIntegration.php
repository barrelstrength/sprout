<?php

namespace BarrelStrength\Sprout\forms\integrations;

use Craft;
use craft\fields\PlainText;

/**
 * @property array $defaultAttributes
 * @property array $defaultElementFieldsAsOptions
 */
abstract class ElementIntegration extends Integration
{
    /**
     * The ID of default Author to use when creating an Entry Element
     */
    public ?int $defaultAuthorId = null;

    /**
     * Whether to use the logged in user as the Author of the Entry Element
     */
    public bool $setAuthorToLoggedInUser = false;

    /**
     * Returns a list of the Default Element Fields that can be mapped for this Element Type
     */
    public function getDefaultAttributes(): array
    {
        $fieldInstance = new PlainText();
        $fieldInstance->name = Craft::t('sprout-module-forms', 'Title');
        $fieldInstance->handle = 'title';

        return [$fieldInstance];
    }

    /**
     * Returns a list of the default Element Fields prepared for the Integration::getElementFieldsAsOptions method
     */
    public function getDefaultElementFieldsAsOptions(): array
    {
        $options = [];

        if ($this->getDefaultAttributes()) {
            foreach ($this->getDefaultAttributes() as $item) {
                $options[] = $item;
            }
        }

        return $options;
    }

    public function getElementCustomFieldsAsOptions($elementGroupId): array
    {
        return [];
    }
}

