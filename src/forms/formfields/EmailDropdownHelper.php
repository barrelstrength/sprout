<?php

namespace BarrelStrength\Sprout\forms\formfields;

class EmailDropdownHelper
{
    public static function obfuscateEmailAddresses($options, $value = null): array
    {
        foreach ($options as $key => $option) {
            $options[$key]['value'] = $key;

            $options[$key]['selected'] = $option['value'] == $value ? 1 : 0;
        }

        return $options;
    }

    public static function isAnyOptionsSelected($options, $value = null): bool
    {
        if (!empty($options)) {
            foreach ($options as $option) {
                if ($option->selected == true || ($value !== null && $value == $option->value)) {
                    return true;
                }
            }
        }

        return false;
    }
}
