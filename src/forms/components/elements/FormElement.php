<?php

namespace BarrelStrength\Sprout\forms\components\elements;

use BarrelStrength\Sprout\core\components\fieldlayoutelements\MediaBoxField;
use BarrelStrength\Sprout\core\components\fieldlayoutelements\RelationsTableField;
use BarrelStrength\Sprout\core\helpers\ComponentHelper;
use BarrelStrength\Sprout\core\relations\RelationsTableInterface;
use BarrelStrength\Sprout\core\relations\RelationsHelper;
use BarrelStrength\Sprout\forms\components\datasources\SubmissionsDataSource;
use BarrelStrength\Sprout\forms\components\elements\conditions\FormCondition;
use BarrelStrength\Sprout\forms\components\elements\db\FormElementQuery;
use BarrelStrength\Sprout\forms\components\elements\fieldlayoutelements\FormBuilderField;
use BarrelStrength\Sprout\forms\components\elements\fieldlayoutelements\IntegrationsField;
use BarrelStrength\Sprout\forms\components\formfields\MissingFormField;
use BarrelStrength\Sprout\forms\components\formtypes\DefaultFormType;
use BarrelStrength\Sprout\forms\components\notificationevents\SaveSubmissionNotificationEvent;
use BarrelStrength\Sprout\forms\db\SproutTable;
use BarrelStrength\Sprout\forms\forms\FormBuilderHelper;
use BarrelStrength\Sprout\forms\forms\FormRecord;
use BarrelStrength\Sprout\forms\forms\FormsDataSourceRelationsTrait;
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
use craft\base\Field;
use craft\behaviors\FieldLayoutBehavior;
use craft\db\Query;
use craft\db\Table;
use craft\elements\actions\Delete;
use craft\elements\actions\Duplicate;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\db\ElementQueryInterface;
use craft\elements\User;
use craft\errors\MissingComponentException;
use craft\fieldlayoutelements\TextField;
use craft\helpers\Cp;
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
class FormElement extends Element implements RelationsTableInterface
{
    use FormsDataSourceRelationsTrait;

    // @todo - move to DataSourceRelationsTrait when min version PHP = 8.2
    public const EVENT_REGISTER_DATA_SOURCE_RELATIONS_TYPES = 'registerDataSourcesRelationsTypes';


    public ?string $name = null;

    public ?string $handle = null;

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

        $notifications = $this->getNotifications();

        $newNotificationsButtonText = Craft::t('sprout-module-forms', 'New Notification');
        $newNotificationsReportButtonLink = UrlHelper::cpUrl('sprout/email/' . TransactionalEmailVariant::refHandle() . '/new', [
            'emailVariantSettings' => [
                'emailTypeUid' => 'SELECT_IN_FORM_TYPE_SETTINGS',
                'eventId' => SaveSubmissionNotificationEvent::class,
            ],
            'site' => Cp::requestedSite()->handle,
        ]);

        if ($formType->enableNotificationsTab) {
            $notificationsTab = new FieldLayoutTab();
            $notificationsTab->layout = $fieldLayout;
            $notificationsTab->name = Craft::t('sprout-module-forms', 'Notifications');
            $notificationsTab->uid = 'SPROUT-UID-FORMS-NOTIFICATIONS-TAB';
            $notificationsTab->setElements([
                count($notifications) > 0 ?
                    new RelationsTableField([
                        'attribute' => 'notifications',
                        'rows' => $notifications,
                        'newButtonLabel' => $newNotificationsButtonText,
                        'cpEditUrl' => $newNotificationsReportButtonLink,
                    ]) :
                    new MediaBoxField([
                        'heading' => Craft::t('sprout-module-forms', 'Create your first notification'),
                        'body' => Craft::t('sprout-module-forms', 'Notify visitors or admins after a form has been submitted.'),
                        'addButtonText' => $newNotificationsButtonText,
                        'addButtonLink' => $newNotificationsReportButtonLink,
                        'resourcePath' => '@Sprout/Assets/dist/static/forms/icons/icon.svg',
                    ]),
            ]);
        }

        if ($formType->enableReportsTab) {
            Craft::$app->getView()->registerJs('new DataSourceRelationsTable(' . $this->id . ');');

            $reportsTab = new FieldLayoutTab();
            $reportsTab->layout = $fieldLayout;
            $reportsTab->name = Craft::t('sprout-module-forms', 'Reports');
            $reportsTab->uid = 'SPROUT-UID-FORMS-REPORTS-TAB';
            $reportsTab->setElements([
                $this->getRelationsTableField(),
            ]);
        }

        if ($formType->enableIntegrationsTab) {
            $newIntegrationButtonText = Craft::t('sprout-module-forms', 'New Integration');
            $newIntegrationButtonLink = UrlHelper::cpUrl('sprout/data-studio/new', [
                'type' => SubmissionsDataSource::class,
                'site' => Cp::requestedSite()->handle,
            ]);

            $integrationsTab = new FieldLayoutTab();
            $integrationsTab->layout = $fieldLayout;
            $integrationsTab->name = Craft::t('sprout-module-forms', 'Integrations');
            $integrationsTab->uid = 'SPROUT-UID-FORMS-INTEGRATIONS-TAB';
            $integrationsTab->setElements([
                new IntegrationsField(),
                new MediaBoxField([
                    'heading' => Craft::t('sprout-module-forms', 'Create your first integration'),
                    'body' => Craft::t('sprout-module-forms', 'Send your form submission data to somewhere other than Craft.'),
                    'addButtonText' => $newIntegrationButtonText,
                    'addButtonLink' => $newIntegrationButtonLink,
                    'resourcePath' => '@Sprout/Assets/dist/static/forms/icons/icon.svg',
                ]),
            ]);
        }

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

        if (empty($this->name)) {
            Craft::$app->getView()->registerJs("new Craft.HandleGenerator('#name', '#handle');");
        }

        $tabs = array_merge(
            empty($this->name) ? [$settingsTab] : [],
            [$formBuilderTab],
            $formTypeTabs,
            $formType->enableNotificationsTab && isset($notificationsTab) ? [$notificationsTab] : [],
            $formType->enableReportsTab && isset($reportsTab) ? [$reportsTab] : [],
            $formType->enableIntegrationsTab && isset($integrationsTab) ? [$integrationsTab] : [],
            !empty($this->name) ? [$settingsTab] : [],
        );

        $fieldLayout->setTabs($tabs);

        return $this->_fieldLayout = $fieldLayout;
    }

    public function getSubmissionLayoutUid(): string
    {
        return 'SPROUT-UID-SUBMISSION-LAYOUT';
    }

    public function getSubmissionFieldLayout(): FieldLayout
    {
        if ($this->submissionFieldLayout) {
            $config = Json::decodeIfJson($this->submissionFieldLayout) ?? [];
            $layout = FieldLayout::createFromConfig($config);
            $layout->setTabs(reset($config));
        } else {
            $layout = new FieldLayout();
        }

        // layout is never saved, it has no id and we don't want to store one on the element
        $layout->type = SubmissionElement::class;
        $layout->uid = $this->getSubmissionLayoutUid();

        return $layout;
    }

    public function getFormBuilderSubmissionFieldLayout(): array
    {
        if ($this->submissionFieldLayout) {
            $config = Json::decodeIfJson($this->submissionFieldLayout) ?? [];
        } else {
            $layout = new FieldLayout();
            $layout->type = SubmissionElement::class;

            $layoutTab = new FieldLayoutTab();
            $layoutTab->name = Craft::t('sprout-module-forms', 'Page');
            $layoutTab->uid = StringHelper::UUID();
            $layout->setTabs([$layoutTab]);

            $config = $layout->getConfig();
        }

        $tabs = reset($config);
        $fields = [];
        $uiSettings = [];

        array_walk($tabs, static function(&$tab) use (&$fields, &$uiSettings) {
            if (empty($tab['elements'])) {
                return;
            }

            array_walk($tab['elements'], static function(&$layoutElement) use (&$fields, &$uiSettings) {
                $fieldUid = $layoutElement['fieldUid'] ?? null;

                if (!$fieldUid) {
                    return;
                }

                $field = FormBuilderHelper::getFieldData($fieldUid);
                $fieldData = FormBuilderHelper::getFieldUiSettings($field);

                $fields[$fieldUid] = $fieldData['field'] ?? [];
                $uiSettings[$fieldUid] = $fieldData['uiSettings'] ?? [];

                // merge field and uiSettings into layoutElement
                $layoutElement = array_merge($layoutElement, [
                    'field' => $fieldData['field'] ?? [],
                    'uiSettings' => $fieldData['uiSettings'] ?? [],
                ]);

                //$layoutElement['field'] = $fieldData['field'] ?? [];
                //$layoutElement['uiSettings'] = $fieldData['uiSettings'] ?? [];
            });
        });

        //$config['id'] = $this->id; // Use the Form Element ID as Submission Layout ID
        //$config['type'] = SubmissionElement::class;

        $config['uid'] = $this->getSubmissionLayoutUid();
        $config['tabs'] = $tabs ?? [];
        $config['fields'] = $fields;
        $config['uiSettings'] = $uiSettings;

        return $config;
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

    public function getSubmissionContentTable(): string
    {
        return FormContentTableHelper::getContentTable($this->id);
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
                'url' => UrlHelper::url('sprout/forms'),
            ],
        ];

        /** @var Response|CpScreenResponseBehavior $response */
        $response->crumbs($crumbs);

        Craft::$app->getView()->registerAssetBundle(ConditionBuilderAsset::class);
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

        $record->submissionFieldLayout = $this->submissionFieldLayout;

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
        //if (!$this->_formRecord) {
        //    return;
        //}

        //$oldFieldContext = Craft::$app->content->fieldContext;
        //$oldContentTable = Craft::$app->content->contentTable;
        //
        ////Set our field content and content table to work with our form output
        //Craft::$app->content->fieldContext = $this->getSubmissionFieldContext();
        //Craft::$app->content->contentTable = $this->getSubmissionContentTable();

        // Do we need to create/rename the content table?
        //if (!Craft::$app->db->tableExists($newContentDbTable) && !$this->duplicateOf) {
        //    if ($oldContentDbTable && Craft::$app->db->tableExists($oldContentDbTable)) {
        //        Db::renameTable($oldContentDbTable, $newContentDbTable);
        //    } else {
        //        FormContentTableHelper::createContentTable($newContentDbTable);
        //    }
        //}

        if (ElementHelper::isDraftOrRevision($this)) {
            return;
        }

        $this->updateSubmissionLayout();

        // Reset field context and content table to original values
        //Craft::$app->content->fieldContext = $oldFieldContext;
        //Craft::$app->content->contentTable = $oldContentTable;

        parent::afterPropagate($isNew); // TODO: Change the autogenerated stub
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

        $contentTable = FormContentTableHelper::getContentTable($this->id);

        // Drop the content table
        Craft::$app->getDb()->createCommand()
            ->dropTableIfExists($contentTable)
            ->execute();

        return parent::beforeDelete();
    }

    public function saveFormField($fieldConfig): void
    {
        // @todo how/where do we determine handles for these fields? Do we need to?
        $fieldsService = Craft::$app->getFields();

        $type = $fieldConfig['type'];

        // @todo - what to do with missing fields?
        if ($type === MissingFormField::class) {
            return;
        }

        /** @var Field $field */
        $field = $fieldsService->createField([
            //'id' => $fieldConfig['id'],
            'type' => $type,
            'uid' => $fieldConfig['uid'],
            'name' => $fieldConfig['name'],
            // @todo - handle needs to be dynamic
            'handle' => StringHelper::toHandle($type::displayName()) . '_' . StringHelper::randomString(6),
            'instructions' => $fieldConfig['instructions'],
            // @todo - confirm locales/Sites work as expected
            'translationMethod' => Field::TRANSLATION_METHOD_NONE,
            'settings' => $fieldConfig['settings'] ?? [],
        ]);

        // Set our field context
        Craft::$app->content->fieldContext = $this->getSubmissionFieldContext();
        Craft::$app->content->contentTable = $this->getSubmissionContentTable();

        /** @var Field $oldField */
        if ($oldField = $fieldsService->getFieldByUid($field->uid)) {
            // existing field
            $field->id = $oldField->id;
            $field->handle = $oldField->handle;
            $field->columnSuffix = $oldField->columnSuffix;

            $isNewField = false;
            $oldHandle = $oldField->handle;
        } else {
            // new field
            $isNewField = true;
            $oldHandle = null;
        }

        if (!$fieldsService->saveField($field)) {
            Craft::error('Field does not validate.', __METHOD__);
            // @todo - handle errors on layout
            //$this->addError('submissionFieldLayout', 'Field does not validate.');
            Craft::dd($field->getErrors());
        }

        // Check if the handle is updated to also update the titleFormat, rules and integrations
        if (!$isNewField && $oldHandle !== $field->handle) {
            if (str_contains($this->titleFormat, $oldHandle)) {
                $newTitleFormat = FormsModule::getInstance()->forms->updateTitleFormat($oldHandle, $field->handle, $this->titleFormat);
                $this->titleFormat = $newTitleFormat;
            }

            //FormsModule::getInstance()->forms->updateFieldOnFieldRules($oldHandle, $field->handle, $this);
            //FormsModule::getInstance()->forms->updateFieldOnIntegrations($oldHandle, $field->handle, $this);
        }
    }

    public function updateSubmissionLayout(): void
    {
        // Save Field Layout
        if (!$this->submissionFieldLayout) {
            return;
        }

        $layout = Json::decodeIfJson($this->submissionFieldLayout);

        if (!$layout) {
            return;
        }

        if ($this->duplicateOf) {
            // duplicate fields and remap submissionFieldLayout uids

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

        $newFieldUids = [];

        foreach ($layout['tabs'] as $index => $tab) {
            foreach ($tab['elements'] as $elementIndex => $element) {
                $fieldUid = $element['fieldUid'] ?? null;
                $newFieldUids[] = $fieldUid; // do this here because we might exit

                $fieldData = $element['field'] ?? null;

                // Remove field details. We have the fieldUid and will add back when needed.
                //unset($layout['tabs'][$index]['elements'][$elementIndex]['field']);

                // AFTER PROPAGATE SAVES THINGS TWICE (<sigh>... calling applyDraft after the initial save
                // since we remove our field data from the submissionLayout after the first save, we need to
                // exit here so we don't delete fields below
                // BUT if we remove this, then after we save a field once, somehow field data gets added
                // to the submission Layout field in the db
                //if (empty($element['field'])) {
                //    return;
                //}

                //if (!$fieldUid || empty($element['field']) {
                if (!$fieldUid || empty($fieldData)) {
                    continue;
                }

                // @TODO - extract fields and validate them before saving ANY

                $this->saveFormField($fieldData);
            }
        }

        $oldFieldUids = (new Query())
            ->select(['uid'])
            ->from([Table::FIELDS])
            ->where(['context' => $this->getSubmissionFieldContext()])
            ->column();

        // Delete fields that are no longer in the layout
        $deletedFieldUids = array_diff($oldFieldUids, array_filter($newFieldUids));
        array_walk($deletedFieldUids, static function($fieldUid) {
            if ($field = Craft::$app->getFields()->getFieldByUid($fieldUid)) {
                Craft::$app->getFields()->deleteField($field);
            }
        });

        // remove 'field' attribute from layout.tabs.elements
        array_walk($layout['tabs'], static function(&$tab) {
            array_walk($tab['elements'], static function(&$element) {
                unset($element['field']);
            });
        });

        Craft::$app->getDb()->createCommand()->update(
            SproutTable::FORMS,
            ['submissionFieldLayout' => Json::encode($layout)],
            ['id' => $this->id]
        )->execute();
    }

    /**
     * Returns the fields associated with this form.
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

    public function getCaptchaHtml(): ?string
    {
        if (!$this->enableCaptchas) {
            return null;
        }

        $captchas = FormsModule::getInstance()->formCaptchas->getAllEnabledCaptchas();
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
        // Set title for Unified Element Editor display behavior
        if (isset($config['name'])) {
            $this->title = $config['name'];
        }

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
