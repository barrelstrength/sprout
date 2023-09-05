<?php

namespace BarrelStrength\Sprout\forms\components\elements;

use BarrelStrength\Sprout\core\components\fieldlayoutelements\RelationsTableField;
use BarrelStrength\Sprout\core\helpers\ComponentHelper;
use BarrelStrength\Sprout\core\relations\RelationsHelper;
use BarrelStrength\Sprout\datastudio\components\elements\DataSetElement;
use BarrelStrength\Sprout\forms\components\datasources\SubmissionsDataSource;
use BarrelStrength\Sprout\forms\components\elements\conditions\FormCondition;
use BarrelStrength\Sprout\forms\components\elements\db\FormElementQuery;
use BarrelStrength\Sprout\forms\components\elements\fieldlayoutelements\FormBuilderField;
use BarrelStrength\Sprout\forms\components\elements\fieldlayoutelements\IntegrationsField;
use BarrelStrength\Sprout\forms\components\formtypes\DefaultFormType;
use BarrelStrength\Sprout\forms\components\notificationevents\SaveSubmissionNotificationEvent;
use BarrelStrength\Sprout\forms\db\SproutTable;
use BarrelStrength\Sprout\forms\forms\FormBuilderHelper;
use BarrelStrength\Sprout\forms\forms\FormRecord;
use BarrelStrength\Sprout\forms\FormsModule;
use BarrelStrength\Sprout\forms\formtypes\FormType;
use BarrelStrength\Sprout\forms\formtypes\FormTypeHelper;
use BarrelStrength\Sprout\forms\migrations\helpers\FormContentTableHelper;
use BarrelStrength\Sprout\transactional\components\elements\TransactionalEmailElement;
use BarrelStrength\Sprout\transactional\components\emailvariants\TransactionalEmailVariant;
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
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\db\ElementQueryInterface;
use craft\elements\User;
use craft\errors\MissingComponentException;
use craft\fieldlayoutelements\CustomField;
use craft\fieldlayoutelements\TextField;
use craft\helpers\Cp;
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

    public ?string $formTypeUid = null;

    public bool $enableCaptchas = true;

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

    public static function hasContent(): bool
    {
        return true;
    }

    public static function hasStatuses(): bool
    {
        return true;
    }

    public static function refHandle(): ?string
    {
        return 'form';
    }

    public function getFormType(): FormType
    {
        if ($this->_formType) {
            return $this->_formType;
        }

        $formType = FormTypeHelper::getFormTypeByUid($this->formTypeUid);

        if (!$formType) {
            $formType = FormTypeHelper::getDefaultFormType();
        }

        if (!$formType) {
            throw new MissingComponentException('No Form Type found.');
        }

        $formType->form = $this;

        return $this->_formType = $formType;
    }

    public function setFormType(?FormType $formType): void
    {
        $this->_formType = $formType;
    }

    public function getFieldLayout(): ?FieldLayout
    {
        //if ($this->_fieldLayout) {
        //    return $this->_fieldLayout;
        //}

        $fieldLayout = new FieldLayout([
            'type' => self::class,
        ]);

        // No need to build UI for command line requests
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return $fieldLayout;
        }

        $formType = $this->getFormType();
        $config = FormsModule::getInstance()->getSettings();

        $formTypeTabs = $formType?->getFieldLayout()?->getTabs() ?? [];

        $formBuilderTab = new FieldLayoutTab();
        $formBuilderTab->layout = $fieldLayout;
        $formBuilderTab->name = Craft::t('sprout-module-forms', 'Layout');
        $formBuilderTab->uid = 'SPROUT-UID-FORMS-LAYOUT-TAB';
        $formBuilderTab->setElements([
            new FormBuilderField(),
        ]);

        $notificationsTab = new FieldLayoutTab();
        $notificationsTab->layout = $fieldLayout;
        $notificationsTab->name = Craft::t('sprout-module-forms', 'Notifications');
        $notificationsTab->uid = 'SPROUT-UID-FORMS-NOTIFICATIONS-TAB';
        $notificationsTab->setElements([
            new RelationsTableField([
                'attribute' => 'notifications',
                'rows' => $this->getNotifications(),
                'newButtonLabel' => Craft::t('sprout-module-forms', 'New Notification'),
                'cpEditUrl' => UrlHelper::cpUrl('sprout/email/' . TransactionalEmailVariant::refHandle() . '/new', [
                    'emailVariantSettings' => [
                        'eventId' => SaveSubmissionNotificationEvent::class,
                    ],
                    'site' => Cp::requestedSite()->handle,
                ]),
            ]),
        ]);

        $reportsTab = new FieldLayoutTab();
        $reportsTab->layout = $fieldLayout;
        $reportsTab->name = Craft::t('sprout-module-forms', 'Reports');
        $reportsTab->uid = 'SPROUT-UID-FORMS-REPORTS-TAB';
        $reportsTab->setElements([
            new RelationsTableField([
                'attribute' => 'reports',
                'rows' => $this->getReports(),
                'newButtonLabel' => Craft::t('sprout-module-forms', 'New Data Set'),
                'cpEditUrl' => UrlHelper::cpUrl('sprout/data-studio/new', [
                    'type' => SubmissionsDataSource::class,
                    'site' => Cp::requestedSite()->handle,
                ]),
            ]),
        ]);

        $integrationsTab = new FieldLayoutTab();
        $integrationsTab->layout = $fieldLayout;
        $integrationsTab->name = Craft::t('sprout-module-forms', 'Integrations');
        $integrationsTab->uid = 'SPROUT-UID-FORMS-INTEGRATIONS-TAB';
        $integrationsTab->setElements([
            new IntegrationsField(),
        ]);

        $linkHtml = Links::enhancedLinkFieldHtml([
            'fieldNamespace' => 'redirectUri',
            'selectedLink' => $this->redirectUri,
            'type' => isset($this->redirectUri) ? $this->redirectUri::class : null,
        ]);

        $settingsTab = new FieldLayoutTab();
        $settingsTab->layout = $fieldLayout;
        $settingsTab->name = Craft::t('sprout-module-forms', 'Settings');
        $settingsTab->uid = 'SPROUT-UID-FORMS-SETTINGS-TAB';
        $settingsTab->setElements([
            new TextField([
                'label' => Craft::t('sprout-module-forms', 'Name'),
                'instructions' => Craft::t('sprout-module-forms', 'What this form will be called in the CP.'),
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

        $tabs = array_merge(
            [$formBuilderTab],
            $formTypeTabs,
            $formType->enableNotificationsTab ? [$notificationsTab] : [],
            $formType->enableReportsTab ? [$reportsTab] : [],
            $formType->enableIntegrationsTab ? [$integrationsTab] : [],
            [$settingsTab],
        );

        $fieldLayout->setTabs($tabs);

        return $this->_fieldLayout = $fieldLayout;
    }

    public function getSubmissionFieldLayout(): ?FieldLayout
    {
        if (!$this->submissionFieldLayoutId) {
            return null;
        }

        return Craft::$app->getFields()->getLayoutById($this->submissionFieldLayoutId);
    }

    public function getDefaultSubmissionTabs(): array
    {
        $fieldLayoutTab = new FieldLayoutTab();
        $fieldLayoutTab->name = Craft::t('sprout-module-forms', 'Page');

        return [
            $fieldLayoutTab,
        ];
    }

    public static function createCondition(): ElementConditionInterface
    {
        return Craft::createObject(FormCondition::class, [static::class]);
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
            'id' => Craft::t('sprout-module-forms', 'ID'),
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
        $record->formTypeUid = $this->formTypeUid;
        $record->enableCaptchas = $this->enableCaptchas;

        $record->submissionFieldLayoutId = $this->submissionFieldLayoutId;

        $record->save(false);

        // Set our form record so we can use it in afterPropagate
        $this->_formRecord = $record;

        // Re-save Submission Elements if titleFormat has changed
        $oldTitleFormat = $record->getOldAttribute('titleFormat');

        if ($record->titleFormat !== $oldTitleFormat) {
            FormsModule::getInstance()->submissions->resaveElements($this->getId());
        }

        parent::afterSave($isNew);
    }

    public function afterPropagate(bool $isNew): void
    {
        if (!$this->_formRecord) {
            return;
        }

        $oldFieldContext = Craft::$app->content->fieldContext;
        $oldContentTable = Craft::$app->content->contentTable;

        //Set our field content and content table to work with our form output
        Craft::$app->content->fieldContext = $this->getSubmissionFieldContext();
        Craft::$app->content->contentTable = $this->getSubmissionContentTable();

        $oldHandle = $this->_formRecord->getOldAttribute('handle');
        $oldContentDbTable = FormContentTableHelper::getContentTable($oldHandle);
        $newContentDbTable = FormContentTableHelper::getContentTable($this->_formRecord->handle);

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

        parent::afterPropagate($isNew); // TODO: Change the autogenerated stub
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
                'name' => $element->title,
                'cpEditUrl' => $element->getCpEditUrl(),
                'type' => TransactionalEmailElement::displayName(),
                'actionUrl' => $element->getCpEditUrl(),
            ];
        }, $query->all());
    }

    public function getReports(): array
    {
        $query = DataSetElement::find()
            ->type(SubmissionsDataSource::class);

        return array_map(static function($element) {
            return [
                'name' => $element->name,
                'cpEditUrl' => $element->getCpEditUrl(),
                'type' => $element->getDataSource()::displayName(),
                'actionUrl' => $element->getCpEditUrl(),
            ];
        }, $query->all());
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
     *    'template-override/form-type-folder',
     *    'sprout-forms-form/form-type-folder',
     *    'sprout-forms-settings/form-type-folder', (default templates can be set per Theme/FormType)
     * ]
     */
    public function getIncludeTemplate($name): array
    {
        $settings = FormsModule::getInstance()->getSettings();

        /** @var FormType $formType */
        $formType = FormTypeHelper::getFormTypeByUid($settings->formTypeUid);

        // TODO: Just make this a static class
        $defaultTemplates = new DefaultFormType();

        $includePaths = array_merge($this->additionalTemplates, [
            Craft::getAlias($formType->formTemplate ?? null),
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

                $fieldData = FormBuilderHelper::getFieldUiSettings($field);
                $fieldData['groupName'] = $groupName; // Form Field Sidebar UI specific
                $sourceFields[] = $fieldData;
            }
        }

        // if we have more fields add them to the group 'custom'
        if (count($formFields) > 0) {
            foreach ($formFields as $formField) {
                $fieldData = FormBuilderHelper::getFieldUiSettings($formField);
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
        $rules[] = [['formTypeUid'], 'safe'];
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
        $redirectUri = $values['redirectUri'] ?? null;

        if (!$redirectUri instanceof LinkInterface) {
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
