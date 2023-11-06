<?php

namespace BarrelStrength\Sprout\forms\forms;

class RenderingOptionsHelper
{
    public static function prepareRenderingOptions($allFieldOptions, $currentFieldOptions): array
    {
        $fieldRenderingOptions = [];

        $supportedFieldRenderingOptions = ['id', 'class', 'errorClass', 'data'];

        // Merge and give priority to more specific overrides
        foreach ($supportedFieldRenderingOptions as $fieldRenderingOption) {
            switch ($fieldRenderingOption) {
                case 'id':
                    $fieldRenderingOptions['id'] = $currentFieldOptions['id'] ?? null;
                    break;
                case 'class':
                    $class[] = $allFieldOptions['class'] ?? null;
                    $class[] = $currentFieldOptions['class'] ?? null;
                    $fieldRenderingOptions['class'] = trim(implode(' ', $class));
                    break;
                case 'errorClass':
                    $errorClass[] = $allFieldOptions['errorClass'] ?? null;
                    $errorClass[] = $currentFieldOptions['errorClass'] ?? null;
                    $fieldRenderingOptions['errorClass'] = trim(implode(' ', $errorClass));
                    break;
                case 'data':
                    $globalData = $allFieldOptions['data'] ?? [];
                    $fieldSpecificData = $currentFieldOptions['data'] ?? [];
                    $fieldRenderingOptions['data'] = array_filter(array_merge($globalData, $fieldSpecificData));
                    break;
            }
        }

        return $fieldRenderingOptions;
    }
}
