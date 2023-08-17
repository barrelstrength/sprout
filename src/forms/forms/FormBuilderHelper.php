<?php

namespace BarrelStrength\Sprout\forms\forms;

use BarrelStrength\Sprout\forms\formfields\FormFieldInterface;
use Craft;
use craft\fieldlayoutelements\CustomField;
use craft\fields\MissingField;
use craft\helpers\Component;
use craft\helpers\StringHelper;

class FormBuilderHelper
{
    public static function getFieldData($field)
    {
        /** @var CustomField $type */
        $type = $field::class ?? MissingField::class;

        $svg = Craft::getAlias($field->getSvgIconPath());

        if ($field instanceof FormFieldInterface) {
            $exampleInputHtml = $field->getExampleInputHtml();
        } else {
            $exampleInputHtml = '<div class="missing-component pane"><p class="error">Unable to find component class: ' . $type . '</p></div>';
        }

        $currentUser = Craft::$app->getUser()->getIdentity();
        $showFieldHandles = $currentUser->getPreference('showFieldHandles');

        // Get Default Attributes
        // Exclude any settings attributes (we add them later)
        // And stuff we don't need in the FormBuilder UI
        $fieldData = $field->getAttributes(null, array_merge(array_keys($field->getSettings()), [
            'describedBy',
            'layoutId',
            'columnPrefix',
            'columnSuffix',
            'context',
            'oldHandle',
            'oldSettings',
            'dateCreated',
            'dateUpdated',
            'translationKeyFormat',
            'translationMethod',
            'searchable',
            'uid',
        ]));

        $fieldData['type'] = $type;

        $fieldHandle = $field->handle ?? StringHelper::toCamelCase($field::displayName());

        // Override Attributes with what we know
        $fieldData['id'] = $field->id;
        $fieldData['name'] = $field->name ?? $field::displayName();
        $fieldData['handle'] = $fieldHandle;
        $fieldData['instructions'] = $field->instructions;
        $fieldData['tabId'] = $field->tabId ?? 1;
        $fieldData['required'] = $field->required ?? false;
        $fieldData['sortOrder'] = $field->sortOrder;
        $fieldData['userCondition'] = null;
        $fieldData['elementCondition'] = null;

        $fieldData['uiSettings'] = [
            'displayName' => $field::displayName(),
            'icon' => Component::iconSvg($svg, $field::displayName()),
            'exampleInputHtml' => $exampleInputHtml,
            'fieldHandleHtml' => $showFieldHandles ? $fieldHandle . $field->id : '',
        ];

        // Add settings as separate attribute
        $fieldData['settings'] = $field->getSettings();

        return $fieldData;
    }
}

