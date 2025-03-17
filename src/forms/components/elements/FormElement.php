<?php

namespace BarrelStrength\Sprout\forms\components\elements;

use BarrelStrength\Sprout\core\helpers\ComponentHelper;
use BarrelStrength\Sprout\core\relations\RelationsHelper;
use BarrelStrength\Sprout\forms\components\elements\actions\ChangeFormType;
use BarrelStrength\Sprout\forms\components\elements\conditions\FormCondition;
use BarrelStrength\Sprout\forms\components\elements\db\FormElementQuery;
use BarrelStrength\Sprout\forms\components\elements\fieldlayoutelements\FormBuilderField;
use BarrelStrength\Sprout\forms\components\events\RegisterFormFeatureTabsEvent;
use BarrelStrength\Sprout\forms\components\formtypes\DefaultFormType;
use BarrelStrength\Sprout\forms\components\notificationevents\SaveSubmissionNotificationEvent;
use BarrelStrength\Sprout\forms\db\SproutTable;
use BarrelStrength\Sprout\forms\forms\FormRecord;
use BarrelStrength\Sprout\forms\FormsModule;
use BarrelStrength\Sprout\forms\formtypes\FormType;
use BarrelStrength\Sprout\forms\formtypes\FormTypeHelper;
use BarrelStrength\Sprout\forms\submissions\SubmissionElementFieldLayoutBehavior;
use BarrelStrength\Sprout\forms\submissions\SubmissionsHelper;
use BarrelStrength\Sprout\transactional\components\elements\TransactionalEmailElement;
use Craft;
use craft\base\Element;
use craft\base\Field;
use craft\base\FieldLayoutProviderInterface;
use craft\behaviors\FieldLayoutBehavior;
use craft\db\Query;
use craft\db\Table;
use craft\elements\actions\Delete;
use craft\elements\actions\Duplicate;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\User;
use craft\errors\MissingComponentException;
use craft\fieldlayoutelements\TextField;
use craft\helpers\Db;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;
use craft\web\assets\conditionbuilder\ConditionBuilderAsset;
use craft\web\CpScreenResponseBehavior;
use craft\web\View;
use Throwable;
use Twig\Error\LoaderError as TwigLoaderError;
use Twig\Markup;
use yii\base\ErrorHandler;
use yii\base\Exception;
use yii\web\Response;

/**
 * @mixin FieldLayoutBehavior
 */
class FormElement extends Element implements FieldLayoutProviderInterface
{
    public const INTERNAL_SPROUT_EVENT_REGISTER_FORM_FEATURE_TABS = 'registerInternalSproutFormFeatureTabs';

    public ?string $name = null;

    public ?string $handle = null;

    public ?string $submissionFieldLayoutUid = null;

    private ?string $submissionFieldLayoutConfig = null;

    public ?string $titleFormat = null;

    public ?string $formTypeUid = null;

    public array $formTypeSettings = [];

    public bool $enableCaptchas = true;

    // To soft delete, we need Garbage Collection to add support for removing schema
    // currently there is no way to remove the content table when a soft deleted
    // element is removed. So, hard delete all around!
    public bool $hardDelete = true;

    private ?FieldLayout $_fieldLayout = null;

    private ?FormType $_formType = null;

    private ?FormRecord $_formRecord = null;

    private array $_fields = [];

    public static function displayName(): string
    {
        return Craft::t('sprout-module-forms', 'Form');
    }

    public static function lowerDisplayName(): string
    {
        return Craft::t('sprout-module-forms', 'form');
    }

    public static function pluralDisplayName(): string
    {
        return Craft::t('sprout-module-forms', 'Forms');
    }

    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('sprout-module-forms', 'forms');
    }

    public static function hasStatuses(): bool
    {
        return true;
    }

    public static function refHandle(): ?string
    {
        return 'form';
    }

    public static function hasContent(): bool
    {
        return true;
    }

    public function getFormType(): FormType
    {
        if ($this->_formType) {
            return $this->_formType;
        }

        $formType = $this->getFromTypeFromElement();

        return $this->_formType = $formType;
    }

    public function setFormType(?FormType $formType): void
    {
        $this->_formType = $formType;
    }

    public function getFieldLayout(): FieldLayout
    {
        //if ($this->_fieldLayout) {
        //    return $this->_fieldLayout;
        //}

        $fieldLayout = new FieldLayout([
            'type' => self::class,
            'provider' => $this->getFormType(),
        ]);

        // No need to build UI for command line requests
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return $fieldLayout;
        }

        $formType = $this->getFormType();
        $config = FormsModule::getInstance()->getSettings();

        $formBuilderTab = new FieldLayoutTab();
        $formBuilderTab->layout = $fieldLayout;
        $formBuilderTab->name = Craft::t('sprout-module-forms', 'Layout');
        $formBuilderTab->uid = 'SPROUT-UID-FORMS-LAYOUT-TAB';
        $formBuilderTab->sortOrder = 1;
        $formBuilderTab->setElements([
            new FormBuilderField(),
        ]);

        // Add sortOrder to customized form type tabs
        $formTypeTabs = $formType->getFieldLayout()?->getTabs() ?? [];

        $formTypeTabSortCount = 20;
        foreach ($formTypeTabs as $index => $tab) {
            $formTypeTabs[$index]->layout = $fieldLayout;
            $formTypeTabs[$index]->sortOrder = $formTypeTabSortCount++;
        }

        $settingsTab = new FieldLayoutTab();
        $settingsTab->layout = $fieldLayout;
        $settingsTab->name = Craft::t('sprout-module-forms', 'Settings');
        $settingsTab->uid = 'SPROUT-UID-FORMS-SETTINGS-TAB';
        $settingsTab->sortOrder = 100;
        $settingsTab->setElements([
            new TextField([
                'label' => Craft::t('sprout-module-forms', 'Name'),
                'instructions' => Craft::t('sprout-module-forms', 'What this form will be called in the control panel.'),
                'attribute' => 'name',
                'autofocus' => true,
                'required' => true,
            ]),
            new TextField([
                'label' => Craft::t('sprout-module-forms', 'Handle'),
                'instructions' => Craft::t('sprout-module-forms', 'How you’ll refer to this form in the templates.'),
                'attribute' => 'handle',
                'class' => 'code',
                'required' => true,
            ]),
            new TextField([
                'label' => Craft::t('sprout-module-forms', 'Submission Title Format'),
                'instructions' => Craft::t('sprout-module-forms', "Submission Titles are auto-generated based on the Title Format you enter here. All information contained in the Title will also be searchable. You may use the same syntax here as you would with Dynamic Titles in your Sections. (i.e. {dateCreated|date('Ymd')} {fullName})"),
                'attribute' => 'titleFormat',
                'class' => 'code',
            ]),
        ]);

        if (empty($this->name)) {
            Craft::$app->getView()->registerJs("new Craft.HandleGenerator('#name', '#handle');");
        }

        $defaultTabs = array_merge(
            [$formBuilderTab],
            $formTypeTabs,
            [$settingsTab],
        );

        // Custom INTERNAL_ Event lets other modules add tabs
        $formTabsEvent = new RegisterFormFeatureTabsEvent([
            'element' => $this,
            'fieldLayout' => $fieldLayout,
            'tabs' => $defaultTabs,
        ]);

        $this->trigger(self::INTERNAL_SPROUT_EVENT_REGISTER_FORM_FEATURE_TABS, $formTabsEvent);

        // Reorder tabs by their sortOrder property
        $tabs = $formTabsEvent->tabs;
        usort($tabs, static function($a, $b) {
            return $a->sortOrder <=> $b->sortOrder;
        });

        // Craft overwrites the sort order based on order of tabs
        $fieldLayout->setTabs($tabs);

        return $this->_fieldLayout = $fieldLayout;
    }

    public function getSubmissionFieldLayout(array $config = []): FieldLayout|SubmissionElementFieldLayoutBehavior
    {
        if ($config) {
            return SubmissionsHelper::getSubmissionFieldLayoutFromConfig($this, $config);
        }

        return SubmissionsHelper::getSubmissionFieldLayout($this);
    }

    public static function find(): FormElementQuery
    {
        return new FormElementQuery(static::class);
    }

    public static function createCondition(): ElementConditionInterface
    {
        return Craft::createObject(FormCondition::class, [static::class]);
    }

    protected static function defineSources(string $context = null): array
    {
        $sources = [
            [
                'key' => '*',
                'label' => Craft::t('sprout-module-forms', 'All Forms'),
            ],
        ];

        return $sources;
    }

    protected static function defineActions(string $source = null): array
    {
        $actions = parent::defineActions($source);

        if (Craft::$app->getUser()->getIsAdmin()) {
            $actions[] = ChangeFormType::class;
        }

        $actions[] = Duplicate::class;
        $actions[] = Delete::class;

        return $actions;
    }

    protected static function defineSearchableAttributes(): array
    {
        return [
            'name',
            'handle',
        ];
    }

    protected static function defineSortOptions(): array
    {
        return [
            'name' => Craft::t('sprout-module-forms', 'Form Name'),
            [
                'label' => Craft::t('sprout-module-forms', 'Date Created'),
                'orderBy' => 'elements.dateCreated',
                'attribute' => 'dateCreated',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('sprout-module-forms', 'Date Updated'),
                'orderBy' => 'elements.dateUpdated',
                'attribute' => 'dateUpdated',
                'defaultDir' => 'desc',
            ],
            'id' => Craft::t('sprout-module-forms', 'ID'),
        ];
    }

    protected static function defineTableAttributes(): array
    {
        return [
            'name' => ['label' => Craft::t('sprout-module-forms', 'Name')],
            'handle' => ['label' => Craft::t('sprout-module-forms', 'Handle')],
            'formType' => ['label' => Craft::t('sprout-module-forms', 'Form Type')],
            //'numberOfFields' => ['label' => Craft::t('sprout-module-forms', 'Number of Fields')],
            'totalSubmissions' => ['label' => Craft::t('sprout-module-forms', 'Total Submissions')],
            'formSettings' => ['label' => Craft::t('sprout-module-forms', 'Settings'), 'icon' => 'settings'],
            'id' => ['label' => Craft::t('sprout-module-forms', 'ID')],
            'uid' => ['label' => Craft::t('sprout-module-forms', 'UID')],
            'dateCreated' => ['label' => Craft::t('sprout-module-forms', 'Date Created')],
            'dateUpdated' => ['label' => Craft::t('sprout-module-forms', 'Date Updated')],
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'name',
            'handle',
            //'numberOfFields',
            'totalSubmissions',
            'formSettings',
        ];
    }

    public function behaviors(): array
    {
        return array_merge(parent::behaviors(), [
            'fieldLayout' => [
                'class' => FieldLayoutBehavior::class,
                'elementType' => self::class,
            ],
        ]);
    }

    public function getSubmissionFieldContext(): string
    {
        return 'sproutForms:' . $this->id;
    }

    public function cpEditUrl(): ?string
    {
        $path = UrlHelper::cpUrl('sprout/forms/forms/edit/' . $this->id);

        $params = [];

        if (Craft::$app->getIsMultiSite()) {
            $params['site'] = $this->getSite()->handle;
        }

        return UrlHelper::cpUrl($path, $params);
    }

    public function getPostEditUrl(): ?string
    {
        return $this->cpEditUrl();
    }

    /**
     * Use the name as the string representation.
     */
    public function __toString(): string
    {
        try {
            return (string)$this->name;
        } catch (Throwable $throwable) {
            ErrorHandler::convertExceptionToError($throwable);
        }
    }

    public function getAdditionalButtons(): string
    {
        $html = Craft::$app->getView()->renderTemplate('sprout-module-core/_components/upgrade/button', [
            'module' => FormsModule::getInstance(),
        ]);

        $relations = RelationsHelper::getSourceElementRelations($this);

        $relationsBtnHtml = Craft::$app->getView()->renderTemplate('sprout-module-core/_components/relations/button', [
            'elementId' => $this->id,
            'relations' => $relations,
        ]);

        return $relationsBtnHtml . $html . parent::getAdditionalButtons();
    }

    public function prepareEditScreen(Response $response, string $containerId): void
    {
        $crumbs = [
            [
                'label' => Craft::t('sprout-module-forms', 'Forms'),
                'url' => UrlHelper::url('sprout/forms/forms'),
            ],
        ];

        /** @var Response|CpScreenResponseBehavior $response */
        $response->crumbs($crumbs);

        Craft::$app->getView()->registerAssetBundle(ConditionBuilderAsset::class);
    }

    public function afterSave(bool $isNew): void
    {
        if (!$this->propagating) {
            // Get the form record
            if (!$isNew) {
                $record = FormRecord::findOne($this->id);

                if (!$record instanceof FormRecord) {
                    throw new Exception('Invalid Form ID: ' . $this->id);
                }
            } else {
                $record = new FormRecord();
                $record->id = $this->id;
            }

            $record->name = $this->name;
            $record->titleFormat = $this->titleFormat;
            $record->formTypeUid = $this->formTypeUid;

            $formType = $this->getFormType();
            $formType->setAttributes($this->formTypeSettings, false);

            $record->formTypeSettings = $formType->getSettings();

            if ($this->duplicateOf) {
                $record->name = $this->name . ' - ' . Craft::t('sprout-module-forms', 'Copy');
                $record->handle = StringHelper::toHandle($this->name) . '_' . StringHelper::randomString(6);

            // @todo - Duplicate field layout and fields (since they are independent per form)
                // update all fieldLayoutElement uids and fieldUids to match update
                //$record->submissionFieldLayoutConfig = $this->duplicateSubmissionFieldLayoutConfig();
            } else {
                $record->handle = $this->handle;
            }

            $oldSubmissionFieldLayout = $this->getSubmissionFieldLayout();
            $oldCustomFieldsByUid = $oldSubmissionFieldLayout->getCustomFieldsByUid();

            $newSubmissionFieldLayoutConfig = $this->getSubmissionFieldLayoutConfig();
            $newSubmissionFieldLayout = $this->getSubmissionFieldLayout($newSubmissionFieldLayoutConfig);
            $newCustomFieldsByUid = $newSubmissionFieldLayout->getCustomFieldsByUid();

            $newFieldsToCreate = array_diff_key($newCustomFieldsByUid, $oldCustomFieldsByUid);
            $existingFieldsToUpdate = array_intersect_key($newCustomFieldsByUid, $oldCustomFieldsByUid);
            $fieldsToDelete = array_diff_key($oldCustomFieldsByUid, $newCustomFieldsByUid);

            foreach ($fieldsToDelete as $field) {
                Craft::$app->getFields()->deleteField($field);
            }

            foreach (array_merge($newFieldsToCreate, $existingFieldsToUpdate) as $field) {
                Craft::$app->getFields()->saveField($field);
            }

            Craft::$app->getFields()->saveLayout($newSubmissionFieldLayout);

            $record->submissionFieldLayoutUid = $newSubmissionFieldLayout->uid;

            $record->save(false);

            // Set our form record so we can use it in afterPropagate
            $this->_formRecord = $record;

            // Re-save Submission Elements if titleFormat has changed
            $oldTitleFormat = $record->getOldAttribute('titleFormat');

            if ($record->titleFormat !== $oldTitleFormat) {
                FormsModule::getInstance()->submissions->resaveElements($this->getId());
            }
        }

        parent::afterSave($isNew);
    }

    public function beforeDelete(): bool
    {
        $submissionIds = (new Query())
            ->select(['submissions.id'])
            ->from(['submissions' => SproutTable::FORM_SUBMISSIONS])
            ->where([
                '[[submissions.formId]]' => $this->id,
            ])
            ->column();

        foreach ($submissionIds as $submissionId) {
            Craft::$app->getElements()->deleteElementById($submissionId, SubmissionElement::class);
        }

        Db::delete(Table::FIELDS, [
            'context' => 'sproutForms:' . $this->id,
        ]);

        return parent::beforeDelete();
    }

    public function duplicateSubmissionFieldLayoutConfig(): ?string
    {
        if (!$this->getSubmissionFieldLayoutConfig()) {
            return null;
        }

        $layout = $this->getSubmissionFieldLayoutConfig();

        if (!$layout) {
            return null;
        }

        // @todo - also consider userCondition, elementCondition, rules,
        //  and integrations or anywhere UIDs may be stored as references

        foreach ($layout['tabs'] as $index => $tab) {
            $newTabId = StringHelper::UUID();
            $layout['tabs'][$index]['id'] = $newTabId;
            $layout['tabs'][$index]['uid'] = $newTabId;

            foreach ($tab['elements'] as $elementIndex => $element) {
                $newLayoutElementUid = StringHelper::UUID();
                $layout['tabs'][$index]['elements'][$elementIndex]['uid'] = $newLayoutElementUid;

                $newFieldUid = StringHelper::UUID();
                $layout['tabs'][$index]['elements'][$elementIndex]['fieldUid'] = $newFieldUid;
                $layout['tabs'][$index]['elements'][$elementIndex]['formField']['uid'] = $newFieldUid;
            }
        }

        return Json::encode($layout);
    }

    /**
     * Returns the fields associated with this form.
     */
    public function getFields(): array
    {
        if (!empty($this->_fields)) {
            $this->_fields = [];
        }

        /** @var Field[] $fields */
        $fields = $this->getFieldLayout()?->getCustomFields();

        foreach ($fields as $field) {
            $this->_fields[$field->handle] = $field;
        }

        return $this->_fields;
    }

    public function getClassesOptions($cssClasses = null): array
    {
        $classesIds = [];
        $apiOptions = $this->getFormTemplate()->getCssClassDefaults();
        $options = [
            [
                'label' => Craft::t('sprout-module-forms', 'Select...'),
                'value' => '',
            ],
        ];

        foreach ($apiOptions as $key => $option) {
            $options[] = [
                'label' => $option,
                'value' => $key,
            ];
            $classesIds[] = $key;
        }

        $options[] = [
            'optgroup' => Craft::t('sprout-module-forms', 'Custom CSS Classes'),
        ];

        if (!in_array($cssClasses, $classesIds, true) && $cssClasses) {
            $options[] = [
                'label' => $cssClasses,
                'value' => $cssClasses,
            ];
        }

        $options[] = [
            'label' => Craft::t('sprout-module-forms', 'Add Custom'),
            'value' => 'custom',
        ];

        return $options;
    }

    /**
     * Get the global template used by Sprout Forms
     */
    public function getFormTemplate(): FormType
    {
        $defaultFormType = new DefaultFormType();

        if ($this->formTypeUid) {
            $templatePath = FormTypeHelper::getFormTypeByUid($this->formTypeUid);
            if ($templatePath) {
                return $templatePath;
            }
        }

        return $defaultFormType;
    }

    public function getNotifications(): array
    {
        $query = TransactionalEmailElement::find();
        $query->notificationEventFilterRule([
            'operator' => 'in',
            'values' => [
                SaveSubmissionNotificationEvent::class,
            ],
        ]);

        return array_map(static function($element) {
            return [
                'elementId' => $element->id,
                'name' => $element->title,
                'cpEditUrl' => $element->getCpEditUrl(),
                'type' => TransactionalEmailElement::displayName(),
                'actionUrl' => $element->getCpEditUrl(),
            ];
        }, $query->all());
    }

    public string|array $templateFolderPaths = [];

    public function addTemplateFolderPaths(string|array $templateFolderPaths = []): void
    {
        if (!is_array($templateFolderPaths)) {
            $templateFolderPaths = [$templateFolderPaths];
        }

        $this->templateFolderPaths = $templateFolderPaths;
    }

    /**
     * Enables form include tags to use Twig include overrides and appends name of target form template
     * [
     *    'template-override/form-type-folder',
     *    'sprout-forms-form/form-type-folder',
     *    'sprout-forms-settings/form-type-folder', (default templates can be set per Theme/FormType)
     * ]
     */
    public function getIncludeTemplates($name): array
    {
        $formType = $this->getFormType();

        // Additional Template overrides can be added in templates:
        // {% do form.addTemplateFolderPaths('_overrides/forms-default') %}
        //  {% do form.addTemplateFolderPaths(['_overrides/forms-special', '_overrides/forms-default']) %}
        $includePaths = array_merge($this->templateFolderPaths, $formType->getIncludeTemplates());

        return array_map(static function($path) use ($name) {
            return $path . '/' . $name;
        }, array_filter($includePaths));
    }

    public function getCaptchaHtml(): ?string
    {
        if (!$this->enableCaptchas) {
            return null;
        }

        $captchas = FormsModule::getInstance()->captchas->getAllEnabledCaptchas();
        $captchaHtml = '';

        foreach ($captchas as $captcha) {
            $captcha->form = $this;
            $captchaHtml .= $captcha->getCaptchaHtml();
        }

        return $captchaHtml;
    }

    public function canView(User $user): bool
    {
        return Craft::$app->getUser()->getIdentity()->can(FormsModule::p('editForms'));
    }

    public function canSave(User $user): bool
    {
        return Craft::$app->getUser()->getIdentity()->can(FormsModule::p('editForms'));
    }

    public function canDelete(User $user): bool
    {
        return Craft::$app->getUser()->getIdentity()->can(FormsModule::p('editForms'));
    }

    public function canDuplicate(User $user): bool
    {
        return $user->can(FormsModule::p('editForms'));
    }

    public function getSourceFields(): array
    {
        $formFieldsService = FormsModule::getInstance()->formFields;

        $fieldTypes = $formFieldsService->getFormFieldTypes();
        $formFields = ComponentHelper::typesToInstances($fieldTypes);

        $fieldTypesByGroup = $formFieldsService->getDefaultFormFieldTypesByGroup();

        $sourceFields = [];

        $formType = $this->getFormType();

        foreach ($fieldTypesByGroup as $groupName => $typesInGroup) {
            foreach ($typesInGroup as $type) {

                // if $type is in not array $formType->enabledFormFieldTypes, unset and continue
                if (!in_array($type, $formType->enabledFormFieldTypes, true)) {
                    unset($formFields[$type]);
                    continue;
                }

                $field = $formFields[$type];
                unset($formFields[$type]);

                $sourceFields[] = $field->getFormBuilderSourceFieldData();
            }
        }

        // if we have more fields add them to the group 'custom'
        if (count($formFields) > 0) {
            foreach ($formFields as $formField) {
                $fieldData = $field->getFormBuilderSourceFieldData();
                // Ensure all custom fields are grouped together
                $fieldData['groupName'] = Craft::t('sprout-module-forms', 'Custom');
                $sourceFields[] = $fieldData;
            }
        }

        return $sourceFields;
    }

    /**
     * Returns nothing as we want to manage our own sidebar via the FormBuilderField
     */
    public function getMetadata(): array
    {
        return [];
    }

    /**
     * Returns nothing as we want to manage our own sidebar via the FormBuilderField
     */
    public function getSidebarHtml(bool $static): string
    {
        return "\n";
    }

    public function getAttributeHtml(string $attribute): string
    {
        return match ($attribute) {
            'handle' => '<code>' . $this->handle . '</code>',
            'formType' => $this->getFormType()->name,
            //'numberOfFields' => (new Query())
            //    ->select('COUNT(*)')
            //    ->from([Table::FIELDLAYOUTFIELDS])
            //    ->where(['layoutId' => $this->submissionFieldLayoutId])
            //    ->scalar(),
            'totalSubmissions' => (new Query())
                ->select('COUNT(*)')
                ->from([SproutTable::FORM_SUBMISSIONS])
                ->where(['formId' => $this->id])
                ->scalar(),
            'formSettings' => Html::a('', $this->getCpEditUrl() . '/settings/general', [
                'data-icon' => 'settings',
                'title' => Craft::t('sprout-module-forms', 'Visit form settings'),
            ]),
            default => parent::getAttributeHtml($attribute),
        };
    }

    public function getFromTypeFromElement(): FormType
    {
        $formType = FormTypeHelper::getFormTypeByUid($this->formTypeUid);
        $formType?->setAttributes($this->formTypeSettings, false);
        
        if (!$formType) {
            throw new MissingComponentException('No Form Type found.');
        }

        $formType->form = $this;

        return $formType;
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['name', 'handle'], 'required', 'except' => self::SCENARIO_ESSENTIALS];
        $rules[] = [['name', 'handle'], 'string', 'max' => 255];
        $rules[] = [
            ['handle'],
            HandleValidator::class,
            'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title'],
            'except' => self::SCENARIO_ESSENTIALS,
        ];
        $rules[] = [
            ['name', 'handle'],
            UniqueValidator::class,
            'targetClass' => FormRecord::class,
            'except' => self::SCENARIO_ESSENTIALS,
        ];
        $rules[] = [['titleFormat'], 'required'];

        $rules[] = [['submissionFieldLayoutConfig'], 'safe'];
        $rules[] = [['formTypeUid'], 'safe'];
        $rules[] = [['formTypeSettings'], 'validateFormTypeSettings'];

        return $rules;
    }

    public function validateFormTypeSettings($attribute, $params): void
    {
        $formType = $this->getFromTypeFromElement();

        if (!$formType->validate()) {
            foreach ($formType->getErrors() as $error) {
                // @todo this needs to be a string, but could be handled better and also passed back to the model so the errors display properly inline
                $this->addError($attribute, $error[0] ?? Craft::t('sprout-module-forms', 'Invalid Form Type Settings'));
            }
        }
    }

    public function __construct($config = [])
    {
        // Set title for Unified Element Editor display behavior
        if (isset($config['name'])) {
            $this->title = $config['name'];
        }

        if (isset($config['submissionFieldLayoutConfig'])) {
            $this->setSubmissionFieldLayoutConfig($config['submissionFieldLayoutConfig']);
            unset($config['submissionFieldLayoutConfig']);
        }

        parent::__construct($config);
    }

    public function setSubmissionFieldLayoutConfig(string $value): void
    {
        $this->submissionFieldLayoutConfig = $value;
    }

    public function getSubmissionFieldLayoutConfig(): array
    {
        return Json::decodeIfJson($this->submissionFieldLayoutConfig) ?? [];
    }

    public function render(array $variables = []): Markup
    {
        $formTemplateFolder = null;

        // Resolve the include templates for the form template and use the first one that exists
        // tab and field templates will be resolved in the include tags of our templates using
        // our array of valid template paths from form.getIncludeTemplates()
        foreach ($this->getIncludeTemplates('form') as $includeTemplate) {
            if (Craft::$app->getView()->doesTemplateExist($includeTemplate)) {
                $formTemplateFolder = $includeTemplate;
                break;
            }
        }

        try {
            $variables['form'] = $this;
            $output[] = Craft::$app->getView()->renderTemplate($formTemplateFolder, $variables, View::TEMPLATE_MODE_SITE);
        } catch (TwigLoaderError $e) {
            throw new TwigLoaderError($e->getMessage());
        }

        SubmissionsHelper::setFormMetadataSessionVariable($this);

        return new Markup(implode("\n", $output), Craft::$app->charset);
    }

    public function getHandle(): ?string
    {
        return $this->handle;
    }
}
