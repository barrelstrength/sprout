<?php

namespace BarrelStrength\Sprout\forms\forms;

use BarrelStrength\Sprout\forms\formfields\FormFieldInterface;
use Craft;
use craft\helpers\Component;
use craft\helpers\StringHelper;

class FormBuilderHelper
{
    public static function getFieldUiSettings($field): array
    {
        $svg = Craft::getAlias($field->getSvgIconPath());

        if ($field instanceof FormFieldInterface) {
            $exampleInputHtml = $field->getExampleInputHtml();
        } else {
            $exampleInputHtml = '<div class="missing-component pane"><p class="error">Unable to find component class: ' . $type . '</p></div>';
        }

        $currentUser = Craft::$app->getUser()->getIdentity();
        $showFieldHandles = $currentUser->getPreference('showFieldHandles');

        $fieldHandle = $field->handle ?? StringHelper::toCamelCase($field::displayName());

        $uiSettings = [
            'displayName' => $field::displayName(),
            'icon' => Component::iconSvg($svg, $field::displayName()),
            'exampleInputHtml' => $exampleInputHtml,
            'fieldHandleHtml' => $showFieldHandles ? $fieldHandle . $field->id : '',
        ];

        $fieldSettings = [
            'id' => $field->id,
            'handle' => $field->handle,
            'name' => $field->name ?? $field::displayName(),
            'instructions' => $field->instructions,
            'type' => $field::class,
            'tabId' => $field->tabId ?? 1,
            'sortOrder' => $field->sortOrder,
            'uid' => $field->uid,
            'settings' => $field->getSettings(),
        ];

        return [
            'field' => $fieldSettings,
            'uiSettings' => $uiSettings,
        ];
    }
}

