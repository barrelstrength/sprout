<?php

namespace BarrelStrength\Sprout\forms\formtemplates;

use BarrelStrength\Sprout\forms\components\elements\FormElement;
use BarrelStrength\Sprout\forms\components\formtemplates\CustomFormTemplateSet;
use BarrelStrength\Sprout\forms\components\formtemplates\DefaultFormTemplateSet;
use BarrelStrength\Sprout\forms\FormsModule;
use Craft;
use craft\events\RegisterComponentTypesEvent;
use yii\base\Component;

class FormTemplates extends Component
{
    public const EVENT_REGISTER_FORM_TEMPLATES = 'registerSproutFormTemplates';

    /**
     * Returns all available Form Templates Class Names
     */
    public function getAllFormTemplateTypes(): array
    {
        $formTemplates = [];
        $formTemplates[] = DefaultFormTemplateSet::class;
        $formTemplates[] = CustomFormTemplateSet::class;

        $event = new RegisterComponentTypesEvent([
            'types' => $formTemplates,
        ]);

        $this->trigger(self::EVENT_REGISTER_FORM_TEMPLATES, $event);

        return $event->types;
    }

    public function getFormTemplateTypesInstances(): array
    {
        $formThemes = $this->getAllFormTemplateTypes();

        $instances = [];
        foreach ($formThemes as $formTheme) {
            $instances[$formTheme::getHandle()] = new $formTheme();
        }

        return $instances;
    }

    /**
     * Returns all available Form Templates
     *
     * @return FormTemplates[]
     */
    public function getAllFormTemplates(): array
    {
        $templateTypes = $this->getAllFormTemplateTypes();
        $templates = [];

        foreach ($templateTypes as $templateType) {
            $templates[$templateType] = new $templateType();
        }

        uasort($templates, static function($a, $b): int {
            /**
             * @var $a FormTemplates
             * @var $b FormTemplates
             */
            return $a::displayName() <=> $b::displayName();
        });

        return $templates;
    }

    public function getFormTemplateById($templateId): ?FormTemplateSet
    {
        $formTemplates = null;

        if (class_exists($templateId)) {
            /** @var $formTemplates FormTemplates */
            $formTemplates = new $templateId();
        }

        // TODO: add support for Custom Template Class

        return $formTemplates;
    }

    public function getFormTemplateOptions(FormElement $form = null, bool $generalSettings = false): array
    {
        $options = [];
        if ($generalSettings) {
            $options[] = [
                'optgroup' => Craft::t('sprout-module-forms', 'Global Templates'),
            ];

            $options[] = [
                'label' => Craft::t('sprout-module-forms', 'Default Form Templates'),
                'value' => null,
            ];
        }

        $templates = $this->getAllFormTemplates();
        $templateIds = [];

        if ($generalSettings) {
            $options[] = [
                'optgroup' => Craft::t('sprout-module-forms', 'Form-Specific Templates'),
            ];
        }

        foreach ($templates as $template) {
            $options[] = [
                'label' => $template->getName(),
                'value' => $template::class,
            ];
            $templateIds[] = $template::class;
        }

        $settings = FormsModule::getInstance()->getSettings();

        $templateFolder = $form->formTemplateId ?? $settings->formTemplateId ?? DefaultFormTemplateSet::class;

        $options[] = [
            'optgroup' => Craft::t('sprout-module-forms', 'Custom Template Folder'),
        ];

        if (!in_array($templateFolder, $templateIds, false) && $templateFolder != '') {
            $options[] = [
                'label' => $templateFolder,
                'value' => $templateFolder,
            ];
        }

        $options[] = [
            'label' => Craft::t('sprout-module-forms', 'Add Custom'),
            'value' => 'custom',
        ];

        return $options;
    }

    private function getSitePath($path): string
    {
        return Craft::$app->path->getSiteTemplatesPath() . DIRECTORY_SEPARATOR . $path;
    }
}
