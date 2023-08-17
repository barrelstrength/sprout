<?php

namespace BarrelStrength\Sprout\forms\components\elements;

use BarrelStrength\Sprout\core\helpers\ComponentHelper;
use BarrelStrength\Sprout\core\relations\RelationsHelper;
use BarrelStrength\Sprout\forms\components\elements\db\FormElementQuery;
use BarrelStrength\Sprout\forms\components\elements\fieldlayoutelements\FormBuilderField;
use BarrelStrength\Sprout\forms\components\formthemes\DefaultFormTheme;
use BarrelStrength\Sprout\forms\components\notificationevents\SaveSubmissionNotificationEvent;
use BarrelStrength\Sprout\forms\db\SproutTable;
use BarrelStrength\Sprout\forms\forms\FormBuilderHelper;
use BarrelStrength\Sprout\forms\forms\FormRecord;
use BarrelStrength\Sprout\forms\FormsModule;
use BarrelStrength\Sprout\forms\formthemes\FormTheme;
use BarrelStrength\Sprout\forms\formthemes\FormThemeHelper;
use BarrelStrength\Sprout\forms\migrations\helpers\FormContentTableHelper;
use BarrelStrength\Sprout\transactional\components\elements\TransactionalEmailElement;
use BarrelStrength\Sprout\uris\links\AbstractLink;
use BarrelStrength\Sprout\uris\links\LinkInterface;
use BarrelStrength\Sprout\uris\links\Links;
use Craft;
use craft\base\Element;
use craft\base\FieldInterface;
use craft\behaviors\FieldLayoutBehavior;
use craft\db\Query;
use craft\db\Table;
use craft\elements\actions\Delete;
use craft\elements\actions\Duplicate;
use craft\elements\db\ElementQueryInterface;
use craft\elements\User;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\fieldlayoutelements\CustomField;
use craft\fieldlayoutelements\Html as HtmlFieldLayoutElement;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
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
use Throwable;
use yii\base\ErrorHandler;
use yii\base\Exception;
use yii\web\Response;

/**
 * @mixin FieldLayoutBehavior
 */
class FormElement extends Element
{
    public ?string $name = null;

    public ?string $handle = null;

    public ?string $oldHandle = null;

    public ?int $submissionFieldLayoutId = null;

    public ?string $submissionFieldLayout = null;

    public ?string $titleFormat = null;

    public bool $displaySectionTitles = false;

    public ?LinkInterface $redirectUri = null;

    public string $submissionMethod = 'sync';

    public string $errorDisplayMethod = 'inline';

    public string $messageOnSuccess = '';

    public string $messageOnError = '';

    public string $submitButtonText = '';

    public bool $saveData = true;

    public ?string $formThemeUid = null;

    public bool $enableCaptchas = true;

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

    public static function hasContent(): bool
    {
        return true;
    }

    public static function hasStatuses(): bool
    {
        return true;
    }

    public static function statuses(): array
    {
        $statuses = parent::statuses();
        $statuses[self::STATUS_ENABLED] = Craft::t('sprout-module-forms', 'Accepting Submissions');
        $statuses[self::STATUS_DISABLED] = Craft::t('sprout-module-forms', 'Closed to Submissions');

        return $statuses;
    }

    public static function refHandle(): ?string
    {
        return 'form';
    }

    public static function defineNativeFields(DefineFieldLayoutFieldsEvent $event): void
    {
        /** @var FieldLayout $fieldLayout */
        $fieldLayout = $event->sender;

        if ($fieldLayout->type === self::class) {
            $event->fields[] = FormBuilderField::class;
        }
    }

    private ?FieldLayout $_fieldLayout = null;

    public function getFieldLayout(): ?FieldLayout
    {
        if ($this->_fieldLayout) {
            return $this->_fieldLayout;
        }

        $this->_fieldLayout = new FieldLayout([
            'type' => self::class,
        ]);

        // No need to build UI for command line requests
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return $this->_fieldLayout;
        }

        $integrations = FormsModule::getInstance()->formIntegrations->getIntegrationsByFormId($this->id);
        $config = FormsModule::getInstance()->getSettings();

        $contentTabs = $config->getFieldLayout()->getTabs();
        $contentTab = reset($contentTabs) ?: [];

        $formBuilderTab = new FieldLayoutTab();
        $formBuilderTab->layout = $this->_fieldLayout;
        $formBuilderTab->name = Craft::t('sprout-module-forms', 'Layout');
        $formBuilderTab->uid = StringHelper::UUID();
        $formBuilderTab->setElements([
            new FormBuilderField(),
        ]);

        $templatesHtml = Craft::$app->getView()->renderTemplate('sprout-module-forms/forms/_settings/templates.twig', [
            'form' => $this,
            'config' => $config,
        ]);

        $templatesTab = new FieldLayoutTab();
        $templatesTab->layout = $this->_fieldLayout;
        $templatesTab->name = Craft::t('sprout-module-forms', 'Design');
        $templatesTab->uid = StringHelper::UUID();
        $templatesTab->setElements([
            new HtmlFieldLayoutElement($templatesHtml),
        ]);

        $notificationsHtml = Craft::$app->getView()->renderTemplate('sprout-module-forms/forms/_settings/notifications.twig', [
            'form' => $this,
            'config' => $config,
        ]);

        $notificationsTab = new FieldLayoutTab();
        $notificationsTab->layout = $this->_fieldLayout;
        $notificationsTab->name = Craft::t('sprout-module-forms', 'Notifications');
        $notificationsTab->uid = StringHelper::UUID();
        $notificationsTab->setElements([
            new HtmlFieldLayoutElement($notificationsHtml),
        ]);

        $integrationsHtml = Craft::$app->getView()->renderTemplate('sprout-module-forms/forms/_settings/integrations.twig', [
            'form' => $this,
            'integrations' => $integrations,
            'config' => $config,
        ]);

        $integrationsTab = new FieldLayoutTab();
        $integrationsTab->layout = $this->_fieldLayout;
        $integrationsTab->name = Craft::t('sprout-module-forms', 'Integrations');
        $integrationsTab->uid = StringHelper::UUID();
        $integrationsTab->setElements([
            new HtmlFieldLayoutElement($integrationsHtml),
        ]);

        $linkHtml = Links::enhancedLinkFieldHtml([
            'fieldNamespace' => 'redirectUri',
            'selectedLink' => $this->redirectUri,
            'type' => isset($this->redirectUri) ? $this->redirectUri::class : null,
        ]);

        $settingsHtml = Craft::$app->getView()->renderTemplate('sprout-module-forms/forms/_settings/general.twig', [
            'form' => $this,
            'config' => $config,
            'linkHtml' => $linkHtml,
        ]);

        $settingsTab = new FieldLayoutTab();
        $settingsTab->layout = $this->_fieldLayout;
        $settingsTab->name = Craft::t('sprout-module-forms', 'Settings');
        $settingsTab->uid = StringHelper::UUID();
        $settingsTab->setElements([
            new HtmlFieldLayoutElement($settingsHtml),
        ]);

        $this->_fieldLayout->setTabs([
            $formBuilderTab,
            $contentTab,
            $templatesTab,
            $notificationsTab,
            $integrationsTab,
            $settingsTab,
        ]);

        return $this->_fieldLayout;
    }

    public function getSubmissionFieldLayout(): ?FieldLayout
    {
        if (!$this->submissionFieldLayoutId) {
            return null;
        }

        return Craft::$app->getFields()->getLayoutById($this->submissionFieldLayoutId);
    }

    public function getSubmissionFieldLayoutTabs(): array
    {
        $tabs = $this->getSubmissionFieldLayout()?->getTabs();

        // Provide default Tab if no layout exists
        if (!$tabs) {
            $tabs = $this->getDefaultSubmissionTabs();
        }

        return $tabs;
    }

    public function getDefaultSubmissionTabs(): array
    {
        $fieldLayoutTab = new FieldLayoutTab();
        $fieldLayoutTab->name = Craft::t('sprout-module-forms', 'Page');

        return [
            $fieldLayoutTab,
        ];
    }

    /**
     * @return FormElementQuery The newly created [[FormQuery]] instance.
     */
    public static function find(): ElementQueryInterface
    {
        return new FormElementQuery(static::class);
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
        ];
    }

    protected static function defineTableAttributes(): array
    {
        return [
            'name' => ['label' => Craft::t('sprout-module-forms', 'Name')],
            'handle' => ['label' => Craft::t('sprout-module-forms', 'Handle')],
            'numberOfFields' => ['label' => Craft::t('sprout-module-forms', 'Number of Fields')],
            'totalSubmissions' => ['label' => Craft::t('sprout-module-forms', 'Total Submissions')],
            'formSettings' => ['label' => Craft::t('sprout-module-forms', 'Settings'), 'icon' => 'settings'],
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'name',
            'handle',
            'numberOfFields',
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

    public function getSubmissionContentTable(): string
    {
        return FormContentTableHelper::getContentTable($this->handle);
    }

    public function cpEditUrl(): ?string
    {
        $path = UrlHelper::cpUrl('sprout/forms/edit/' . $this->id);

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
     *
     * @noinspection PhpInconsistentReturnPointsInspection
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
                'url' => UrlHelper::url('sprout/forms'),
            ],
        ];

        /** @var Response|CpScreenResponseBehavior $response */
        $response->crumbs($crumbs);

        Craft::$app->getView()->registerAssetBundle(ConditionBuilderAsset::class);
    }

    public function getIsNew($id): bool
    {
        return (!$id || str_starts_with($id, 'new'));
    }

    public function beforeSave(bool $isNew): bool
    {
        return parent::beforeSave($isNew);
    }

    public function afterSave(bool $isNew): void
    {
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
        $record->handle = $this->handle;
        $record->titleFormat = $this->titleFormat;
        $record->displaySectionTitles = $this->displaySectionTitles;
        $record->redirectUri = Db::prepareValueForDb($this->redirectUri);
        $record->saveData = $this->saveData;
        $record->submissionMethod = $this->submissionMethod;
        $record->errorDisplayMethod = $this->errorDisplayMethod;
        $record->messageOnSuccess = $this->messageOnSuccess;
        $record->messageOnError = $this->messageOnError;
        $record->submitButtonText = $this->submitButtonText;
        $record->formThemeUid = $this->formThemeUid;
        $record->enableCaptchas = $this->enableCaptchas;

        if (!ElementHelper::isDraftOrRevision($this)) {
            $oldFieldContext = Craft::$app->content->fieldContext;
            $oldContentTable = Craft::$app->content->contentTable;

            //Set our field content and content table to work with our form output
            Craft::$app->content->fieldContext = $this->getSubmissionFieldContext();
            Craft::$app->content->contentTable = $this->getSubmissionContentTable();

            $oldHandle = $record->getOldAttribute('handle');
            $oldContentDbTable = FormContentTableHelper::getContentTable($oldHandle);
            $newContentDbTable = FormContentTableHelper::getContentTable($record->handle);

            // Do we need to create/rename the content table?
            if (!Craft::$app->db->tableExists($newContentDbTable) && !$this->duplicateOf) {
                if ($oldContentDbTable && Craft::$app->db->tableExists($oldContentDbTable)) {
                    Db::renameTable($oldContentDbTable, $newContentDbTable);
                } else {
                    FormContentTableHelper::createContentTable($newContentDbTable);
                }
            }

            $this->updateSubmissionLayout();

            // Reset field context and content table to original values
            Craft::$app->content->fieldContext = $oldFieldContext;
            Craft::$app->content->contentTable = $oldContentTable;
        }

        $record->submissionFieldLayoutId = $this->submissionFieldLayoutId;

        $record->save(false);

        // Re-save Submission Elements if titleFormat has changed
        $oldTitleFormat = $record->getOldAttribute('titleFormat');

        if ($record->titleFormat !== $oldTitleFormat) {
            FormsModule::getInstance()->submissions->resaveElements($this->getId());
        }

        parent::afterSave($isNew);
    }

    public function updateSubmissionLayout(): void
    {
        // Save Field Layout
        if (!$this->submissionFieldLayout) {
            return;
        }

        // This should exist in $this->submissionFieldLayout as a list of IDs I can just act on
        // If they are deleted, just delete them!
        $deletedFieldIds = [];

        $fieldsService = Craft::$app->getFields();

        if ($this->duplicateOf) {

            // remap submissionFieldLayout and duplicate fields, etc.
            // Does craft have a method for this?
            // id, uid
            // do userCondition and elementCondition need to be updated?
            // they probably store uids as references
            // fields.id, fields.userCondition, fields.elementCondition

            // refreshUUID() method
            // find all UUIDs in text blob with regex
            // map every old UUID to a new UUID
            // and do a find/replace
        }

        $layoutConfig = Json::decode($this->submissionFieldLayout);
        $layoutConfig = reset($layoutConfig);
        $layoutTabs = [];

        // Loop through Form Tabs
        foreach ($layoutConfig as $layoutTab) {

            $layoutFieldData = $layoutTab['fields'] ?? [];
            $layoutUserCondition = $layoutTab['userCondition'];
            $layoutElementCondition = $layoutTab['elementCondition'];

            unset(
                $layoutTab['fields'],
                $layoutTab['userCondition'],
                $layoutTab['elementCondition']
            );

            if ($this->getIsNew($layoutTab['id'])) {
                $tabUUID = StringHelper::UUID();
                $layoutTab['uid'] = $tabUUID;
            }

            $layoutFieldElements = [];

            foreach ($layoutFieldData as $layoutField) {

                $layoutFieldUserCondition = $layoutField['userCondition'];
                $layoutFieldElementCondition = $layoutField['elementCondition'];

                unset(
                    $layoutField['userCondition'],
                    $layoutField['elementCondition']
                );

                if ($this->getIsNew($layoutField['id'])) {
                    $fieldUUID = StringHelper::UUID();
                    $layoutField['uid'] = $fieldUUID;

                    if ($layoutField['handle'] === null) {
                        $layoutField['handle'] = StringHelper::toCamelCase($layoutField['name']);
                    }
                }

                $layoutField['context'] = 'sproutForms:' . Db::uidById(SproutTable::FORMS, $this->id);
                $layoutField['layoutId'] = $this->submissionFieldLayoutId;
                $layoutField['tabId'] = $layoutTab['id'];

                // Unset values only needed for front-end UI
                // @todo - can I just remove this from the data submission?
                unset($layoutField['groupName']);

                //                $layoutField['id'] = (int)$layoutField['id'];
                $field = $fieldsService->createField($layoutField);

                // 1. Create actual Field craft_fields
                if (!$fieldsService->saveField($field)) {
                    throw new Exception('Couldn’t save form field: ' . Json::encode($field->getErrors()));
                }

                // 2. Create Field Layout Field craft_fieldlayoutfields
                // 3. craft_fieldlayouttabs.elements
                // 4. If HANDLE changed, update column in formcontent_table?

                $layoutFieldElement = new CustomField($field, [
                    'required' => $layoutField['required'] ?? false,
                    'label' => $layoutField['name'],
                    'uid' => $field->uid,
                ]);

                $layoutFieldElements[] = $layoutFieldElement;
            }

            $tabModel = new FieldLayoutTab([
                'layoutId' => $this->submissionFieldLayoutId,
                'uid' => $layoutTab['uid'],
                'name' => $layoutTab['name'],
                'elements' => $layoutFieldElements,
            ]);

            $layoutTabs[] = $tabModel;
        }

        $submissionFieldLayout = $this->getSubmissionFieldLayout();
        $submissionFieldLayout->setTabs($layoutTabs);
        $fieldsService->saveLayout($submissionFieldLayout, false);

        // FIRST, need to:
        // 1. create fields so we can relate to them
        // 2. identify any fields we changed so we can update the
        // content columns (check for existing fieldIds with different handles than were submitted)

        /// Loop through tabs and save/update/delete TAB/FIELDS
        //  Instantiate each tab/field and add to layout
        // Check if content table names changed and rename them...

        //        foreach ($layoutTabs['tabs'] as $key => $tab) {

        // get layout tab from db
        //            $fieldLayoutTab = \craft\records\FieldLayoutTab::findOne($tab->id);

        //                    Db::update(Table::MATRIXBLOCKS_OWNERS, [
        //                        'sortOrder' => $this->sortOrder ?? 0,
        //                    ], [
        //                        'blockId' => $this->id,
        //                        'ownerId' => $this->ownerId,
        //                    ]);

        //                $layout->id =
        //            $fieldLayoutTab = new FieldLayoutTab();
        //            $fieldLayoutTab->layoutId = $fieldLayout->id;
        //            $fieldLayoutTab->name = Craft::t('sprout-module-forms', 'Page');
        //            $fieldLayoutTab->sortOrder = $key;
        //        }

        //        Craft::$app->getFields()->saveLayout($fieldLayout);
        //        $record->submissionFieldLayoutId = $fieldLayout->id;

        //        Craft::$app->getFields()->deleteLayoutById($this->submissionFieldLayoutId);
        //
        //        Db::delete(Table::FIELDS, [
        //            'elementId' => array_keys($deletedFieldIds),
        //        ]);

        //        $this->submissionFieldLayoutId = $submissionFieldLayoutId;
    }

    /**
     * Returns the fields associated with this form.
     *
     * @return FormField[]
     */
    public function getFields(): array
    {
        if ($this->_fields === null) {
            $this->_fields = [];

            /** @var FormField[] $fields */
            $fields = $this->getFieldLayout()->getCustomFields();

            foreach ($fields as $field) {
                $this->_fields[$field->handle] = $field;
            }
        }

        return $this->_fields;
    }

    /**
     *
     * @return FieldInterface|null
     */
    //public function getField(string $handle): ?FormField
    //{
    //    $fields = $this->getFields();
    //
    //    if (is_string($handle) && !empty($handle)) {
    //        return $fields[$handle] ?? null;
    //    }
    //
    //    return null;
    //}

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
    public function getFormTemplate(): FormTheme
    {
        $defaultFormTheme = new DefaultFormTheme();

        if ($this->formThemeUid) {
            $templatePath = FormThemeHelper::getFormThemeByUid($this->formThemeUid);
            if ($templatePath) {
                return $templatePath;
            }
        }

        return $defaultFormTheme;
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

        return $query->all();
    }

    public string|array $additionalTemplates = [];

    public function addTemplateOverridePaths(string|array $additionalTemplates = []): void
    {
        if (!is_array($additionalTemplates)) {
            $additionalTemplates = [$additionalTemplates];
        }

        $this->additionalTemplates = $additionalTemplates;
    }

    /**
     * Enables form include tags to use Twig include overrides and appends name of target form template
     * [
     *    'template-override/theme-id',
     *    'sprout-forms-form/theme-id',
     *    'sprout-forms-settings/theme-id', (default templates can be set per Theme/FormType)
     * ]
     */
    public function getIncludeTemplate($name): array
    {
        $settings = FormsModule::getInstance()->getSettings();

        /** @var FormTheme $formTheme */
        $formTheme = FormThemeHelper::getFormThemeByUid($settings->formThemeUid);

        // TODO: Just make this a static class
        $defaultTemplates = new DefaultFormTheme();

        $includePaths = array_merge($this->additionalTemplates, [
            Craft::getAlias($formTheme->formTemplate ?? null),
            Craft::getAlias($defaultTemplates->formTemplate),
        ]);

        return array_map(static function($path) use ($name) {
            return $path . '/' . $name;
        }, $includePaths);
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

        foreach ($fieldTypesByGroup as $groupName => $typesInGroup) {
            foreach ($typesInGroup as $type) {
                $field = $formFields[$type];
                unset($formFields[$type]);
                $fieldData = FormBuilderHelper::getFieldData($field);
                $fieldData['groupName'] = $groupName; // Form Field Sidebar UI specific
                $sourceFields[] = $fieldData;
            }

            // if we have more fields add them to the group 'custom'
            if (count($formFields) > 0) {
                foreach ($formFields as $formField) {
                    $fieldData = FormBuilderHelper::getFieldData($formField);
                    $fieldData['groupName'] = Craft::t('sprout-module-forms', 'Custom');
                    $sourceFields[] = $fieldData;
                }
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

    protected function tableAttributeHtml(string $attribute): string
    {
        return match ($attribute) {
            'handle' => '<code>' . $this->handle . '</code>',
            'numberOfFields' => (new Query())
                ->select('COUNT(*)')
                ->from([Table::FIELDLAYOUTFIELDS])
                ->where(['layoutId' => $this->submissionFieldLayoutId])
                ->scalar(),
            'totalSubmissions' => (new Query())
                ->select('COUNT(*)')
                ->from([SproutTable::FORM_SUBMISSIONS])
                ->where(['formId' => $this->id])
                ->scalar(),
            'formSettings' => Html::a('', $this->getCpEditUrl() . '/settings/general', [
                'data-icon' => 'settings',
                'title' => Craft::t('sprout-module-forms', 'Visit form settings'),
            ]),
            default => parent::tableAttributeHtml($attribute),
        };
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['name', 'handle'], 'required'];
        $rules[] = [['name', 'handle'], 'string', 'max' => 255];
        $rules[] = [
            ['handle'],
            HandleValidator::class,
            'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title'],
        ];
        $rules[] = [
            ['name', 'handle'],
            UniqueValidator::class,
            'targetClass' => FormRecord::class,
        ];

        $rules[] = [['submissionFieldLayoutId'], 'safe'];
        $rules[] = [['submissionFieldLayout'], 'safe'];
        $rules[] = [['titleFormat'], 'required'];
        $rules[] = [['displaySectionTitles'], 'safe'];
        $rules[] = [
            ['redirectUri'], function($attribute) {
                /** @var AbstractLink $link */
                $link = $this->$attribute;
                if ($link && !$link->validate()) {
                    $this->addError($attribute, $link->getErrorSummary(true)[0]);
                }
            },
        ];
        $rules[] = [['submissionMethod'], 'safe'];
        $rules[] = [['errorDisplayMethod'], 'safe'];
        $rules[] = [['messageOnSuccess'], 'safe'];
        $rules[] = [['messageOnError'], 'safe'];
        $rules[] = [['submitButtonText'], 'safe'];
        $rules[] = [['saveData'], 'safe'];
        $rules[] = [['formThemeUid'], 'safe'];
        $rules[] = [['enableCaptchas'], 'safe'];

        return $rules;
    }

    public function __construct($config = [])
    {
        if (isset($config['redirectUri'])) {
            $config['redirectUri'] = Links::toLinkField($config['redirectUri']) ?: null;
        }
        parent::__construct($config);
    }

    public function setAttributes($values, $safeOnly = true): void
    {
        if ($this->redirectUri instanceof LinkInterface) {
            $values['redirectUri'] = $this->redirectUri;
        } else {
            $type = $values['redirectUri']['type'] ?? null;

            if ($type !== null) {
                $attributes = array_merge(
                    ['type' => $type],
                    $values['redirectUri'][$type] ?? []
                );

                $values['redirectUri'] = Links::toLinkField($attributes) ?: null;
            }
        }

        parent::setAttributes($values, $safeOnly);
    }
}
