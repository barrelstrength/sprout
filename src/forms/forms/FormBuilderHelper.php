<?php

namespace BarrelStrength\Sprout\forms\forms;

use BarrelStrength\Sprout\forms\components\formfields\MissingFormField;
use BarrelStrength\Sprout\forms\formfields\FormFieldInterface;
use Craft;
use craft\base\FieldInterface;
use craft\helpers\Component;
use craft\helpers\StringHelper;
use craft\records\Field as FieldRecord;

class FormBuilderHelper
{
    public static function getFieldData(string $fieldUid = null): ?FieldInterface
    {
        if ($fieldUid) {
            $field = Craft::$app->getFields()->getFieldByUid($fieldUid);
        }

        return $field ?? new MissingFormField();
    }

    public static function getFieldUiSettings($field): array
    {
        $svg = Craft::getAlias($field->getSvgIconPath());

        if ($field instanceof FormFieldInterface) {
            $exampleInputHtml = $field->getExampleInputHtml();
        } else {
            $exampleInputHtml = '<div class="missing-component pane"><p class="error">Unable to find component class: ' . $field::class . '</p></div>';
        }

        $currentUser = Craft::$app->getUser()->getIdentity();
        //$showFieldHandles = $currentUser->getPreference('showFieldHandles');

        $uiSettings = [
            'displayName' => $field::displayName(),
            'defaultHandle' => StringHelper::toHandle($field::displayName()),
            'icon' => Component::iconSvg($svg, $field::displayName()),
            'exampleInputHtml' => $exampleInputHtml,
            // @todo - can we remove this after handle updates?
            //'fieldHandleHtml' => $showFieldHandles ? $field->handle . $field->id : '',
            'fieldUid' => $field->uid,
        ];

        $fieldSettings = [
            //'id' => $field->id,
            'name' => $field->name ?? $field::displayName(),
            'handle' => $field->handle, // Default created in JS
            'instructions' => $field->instructions,
            'type' => $field::class,
            'tabUid' => $field->tabUid ?? 1,
            'sortOrder' => $field->sortOrder,
            'uid' => $field->uid,
            'settings' => $field->getSettings(),
        ];

        return [
            'field' => $fieldSettings,
            'uiSettings' => $uiSettings,
        ];
    }

    /**
     * Create a sequential string for the "name" and "handle" fields if they are already taken
     *
     * @return null|string|string[]
     */
    public function getFieldAsNew($field, $value)
    {
        $i = 1;
        $band = true;

        do {
            if ($field == 'handle') {
                // Append a number to our handle to ensure it is unique
                $newField = $value . $i;

                $form = $this->getFieldValue($field, $newField);

                if (!$form instanceof FieldRecord) {
                    $band = false;
                }
            } else {
                // Add spaces before any capital letters in our name
                $newField = preg_replace('#([a-z])([A-Z])#', '$1 $2', $value);
                $band = false;
            }

            $i++;
        } while ($band);

        return $newField;
    }

    //public function getFieldAsNew($field, $value): string
    //{
    //    $i = 1;
    //    $band = true;
    //
    //    do {
    //        $newField = $field == 'handle' ? $value . $i : $value . ' ' . $i;
    //        $form = $this->getFieldValue($field, $newField);
    //        if (!$form instanceof FormRecord) {
    //            $band = false;
    //        }
    //
    //        $i++;
    //    } while ($band);
    //
    //    return $newField;
    //}
    //
    //public function getFieldValue(string $field, string $value): ?FieldRecord
    //{
    //    return FieldRecord::findOne([
    //        $field => $value,
    //    ]);
    //}

    public function getFieldValue($field, $value): ?FormRecord
    {
        return FormRecord::findOne([
            $field => $value,
        ]);
    }
}
