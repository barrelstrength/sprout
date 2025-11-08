<?php

namespace BarrelStrength\Sprout\forms\formfields;

use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\forms\FormBuilderHelper;
use BarrelStrength\Sprout\forms\forms\RenderingOptionsHelper;
use BarrelStrength\Sprout\forms\FormsModule;
use BarrelStrength\Sprout\forms\formtypes\FormType;
use Craft;
use craft\base\ElementInterface;
use craft\helpers\Html;
use craft\helpers\StringHelper;
use ReflectionClass;
use yii\web\ForbiddenHttpException;

trait FormFieldTrait
{
    public string $cssClasses = '';

    public array $containerAttributes = [];

    public array $inputAttributes = [];

    public bool $isHidden = false;

    public bool $allowEdits = true;

    public mixed $prePopulatedValue = null;

    protected array $compatibleCraftFields = [];

    protected string $originalTemplatesPath;

    protected ?FormType $formType = null;

    public function setFormType(?FormType $formType): void
    {
        $this->formType = $formType;
    }

    public static function getGroupLabel(): string
    {
        return GroupLabel::label(GroupLabel::GROUP_CUSTOM);
    }

    /**
     * The icon to display for your form field
     */
    public function selectorIcon(): string
    {
        return '';
    }

    public function allowRequired(): bool
    {
        return true;
    }

    /**
     * Tells Sprout Forms NOT to wrap your getInputHtml() content inside any extra HTML
     */
    public function isPlainInput(): bool
    {
        return false;
    }

    /**
     * Tells Sprout Forms whether this field will display the allowEdits setting in the Form Builder
     */
    public function isEditable(): bool
    {
        return true;
    }

    /**
     * Tells Sprout Forms to use a <fieldset> instead of a <div> as your field wrapper and
     * NOT to add a for="" attribute to your field's top level label.
     *
     * @note
     * Sprout Forms renders a label with a (for) attribute for all fields.
     * If your field has multiple labels, like radio buttons do for example,
     * it would make sense for your field no to have a (for) attribute at the top level
     * but have them at the radio field level. Individual inputs can then wrap each
     * <input> field in a <label> attribute.
     */
    public function hasMultipleLabels(): bool
    {
        return false;
    }

    /**
     * Display or suppress the label field and behavior
     *
     * @note
     * This is useful for fields like the Opt-In field where
     * a label may not appear above the input.
     */
    public function displayLabel(): bool
    {
        return true;
    }

    /**
     * Display or suppress instructions field.
     *
     * @note
     * This is useful for some field types like the Section Heading field
     * where another textarea field may be the primary to use for output.
     */
    public function displayInstructionsField(): bool
    {
        return true;
    }

    /**
     * The namespace to use when preparing your field's <input> name. This value
     * is also prepended to the field ID.
     *
     * @example
     * All fields default to having name attributes using the fields namespace:
     *
     * <input name="fields[fieldHandle]">
     *
     */
    public function getNamespace(): string
    {
        return 'fields';
    }

    public function getInputTemplate($form): array
    {
        $inputTemplate = 'fields/' . $this->getFieldInputFolder() . '/input';

        return $form->getIncludeTemplates($inputTemplate);
    }

    /**
     * The folder name within the field path to find the input HTML file for this field. By default,
     * the folder is expected to use the Field Class short name.
     *
     * @example
     * The PlainText Field Class would look for it's respective input HTML in
     * `plaintext/input.twig` file within the folder returned by form.getIncludeTemplate()
     *
     */
    public function getFieldInputFolder(): string
    {
        $fieldClassReflection = new ReflectionClass($this);

        return strtolower($fieldClassReflection->getShortName());
    }

    public function getFormBuilderSourceFieldData(): array
    {
        $fieldData['formField'] = FormBuilderHelper::getFormFieldData($this);
        $fieldData['formFieldUi'] = FormBuilderHelper::getFormFieldUiData($this);
        $fieldData['groupName'] = self::getGroupLabel();

        return [
            'groupName' => self::getGroupLabel(),
            'formField' => $fieldData['formField'],
            'formFieldUi' => $fieldData['formFieldUi'],
        ];
    }

    public function getPrePopulatedValue(array $context = []): mixed
    {
        return Craft::$app->getView()->renderObjectTemplate($this->prePopulatedValue, $context);
    }

    public function getRenderingOptions($renderingOptions = []): array
    {
        $allFieldOptions = $renderingOptions['*'] ?? [];
        $currentFieldOptions = $renderingOptions[$this->handle] ?? [];

        $renderingOptions = RenderingOptionsHelper::prepareRenderingOptions($allFieldOptions, $currentFieldOptions);

        return $renderingOptions;
    }

    public function setCompatibleCraftFields(array $types = null): void
    {
        if ($types) {
            $this->compatibleCraftFields = array_merge($types, $this->compatibleCraftFields);
        }
    }

    /**
     * Return a list of compatible Craft Field Types to associate on the Element Integration API
     *
     * @todo - update to be named `getCompatibleFieldTypes()` in v4.x
     */
    public function getCompatibleCraftFieldTypes(): array
    {
        return [];
    }

    public function getPreviewHtml(mixed $value, ElementInterface $element = null): string
    {
        $value = (string)$value;

        return Html::encode(StringHelper::stripHtml($value));
    }

    public function getSlideoutSettingsHtml(): ?string
    {
        $view = Craft::$app->getView();

        $currentUser = Craft::$app->getUser()->getIdentity();

        if (!$currentUser->can(FormsModule::p('editForms'))) {
            throw new ForbiddenHttpException('User is not authorized to perform this action.');
        }

        $html = $view->renderTemplate('sprout-module-forms/forms/_formbuilder/editFormFieldSlideout', [
            'field' => $this,
        ]);

        return $html;
    }

    public function getSettingsHtml(): ?string
    {
        return parent::getSettingsHtml();
    }

    public function getFrontEndInputVariables($value, SubmissionElement $submission, array $renderingOptions = null): array
    {
        return [];
    }
}
