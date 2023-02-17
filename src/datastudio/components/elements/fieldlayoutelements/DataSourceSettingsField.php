<?php

namespace BarrelStrength\Sprout\datastudio\components\elements\fieldlayoutelements;

use BarrelStrength\Sprout\datastudio\components\elements\DataSetElement;
use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseNativeField;
use craft\helpers\Html;

class DataSourceSettingsField extends BaseNativeField
{
    public bool $mandatory = true;

    public string $attribute = 'settings';

    public ?int $maxlength = 255;

    public bool $autofocus = true;

    protected function useFieldset(): bool
    {
        return true;
    }

    protected function showLabel(): bool
    {
        return true;
    }

    protected function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('sprout-module-data-studio', 'Settings');
    }

    protected function selectorLabel(): ?string
    {
        return Craft::t('sprout-module-data-studio', 'Data Set Settings');
    }

    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        if (!$element instanceof DataSetElement) {
            return '';
        }

        $spaceBeforeFirstSetting = '';

        if ($static) {
            $message = Html::tag('p', Craft::t('sprout-module-data-studio', 'Data set settings are visible to users who can edit data sets.'));
            $html = Html::tag('div', $message, ['class' => 'pane']);
        } else {
            $spaceBeforeFirstSetting = Html::tag('br');
            $html = $element->getDataSource()->getSettingsHtml();
        }

        $spaceAfterLastSetting = $element->getErrors('settings') ? Html::tag('hr') : '';

        return $spaceBeforeFirstSetting . $html . $spaceAfterLastSetting;
    }
}
