<?php

namespace BarrelStrength\Sprout\datastudio\components\elements;

use BarrelStrength\Sprout\core\sourcegroups\SourceGroupTrait;
use BarrelStrength\Sprout\datastudio\components\elements\conditions\DataSetCondition;
use BarrelStrength\Sprout\datastudio\components\elements\fieldlayoutelements\DataSourceSettingsField;
use BarrelStrength\Sprout\datastudio\components\elements\fieldlayoutelements\DescriptionField;
use BarrelStrength\Sprout\datastudio\components\elements\fieldlayoutelements\NameField;
use BarrelStrength\Sprout\datastudio\datasets\DataSetRecord;
use BarrelStrength\Sprout\datastudio\datasources\DataSource;
use BarrelStrength\Sprout\datastudio\DataStudioModule;
use BarrelStrength\Sprout\datastudio\visualizations\Visualization;
use Craft;
use craft\base\Element;
use craft\db\ActiveRecord;
use craft\elements\actions\Delete;
use craft\elements\actions\Edit;
use craft\elements\actions\Restore;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\User;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\validators\HandleValidator;
use craft\web\CpScreenResponseBehavior;
use Exception;
use yii\base\InvalidValueException;
use yii\web\Response;

/**
 * @property string|array $settings
 */
class DataSetElement extends Element
{
    use SourceGroupTrait;

    public ?string $name = null;

    public ?string $nameFormat = null;

    public ?string $handle = null;

    public ?string $description = null;

    public ?string $type = null;

    public bool $allowHtml = false;

    public string $sortOrder = 'asc';

    public ?string $sortColumn = null;

    public string $delimiter = ',';

    public string|null $visualizationType = null;

    public array|string|null $visualizationSettings = [];

    public array $settings = [];

    public static function displayName(): string
    {
        return Craft::t('sprout-module-data-studio', 'Data Set');
    }

    public static function lowerDisplayName(): string
    {
        return Craft::t('sprout-module-data-studio', 'data set');
    }

    public static function pluralDisplayName(): string
    {
        return Craft::t('sprout-module-data-studio', 'Data Sets');
    }

    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('sprout-module-data-studio', 'data sets');
    }

    public static function isLocalized(): bool
    {
        return true;
    }

    public static function hasContent(): bool
    {
        return true;
    }

    public static function find(): DataSetElementQuery
    {
        return new DataSetElementQuery(static::class);
    }

    public static function createCondition(): ElementConditionInterface
    {
        return Craft::createObject(DataSetCondition::class, [static::class]);
    }

    public static function defineNativeFields(DefineFieldLayoutFieldsEvent $event): void
    {
        /** @var FieldLayout $fieldLayout */
        $fieldLayout = $event->sender;

        if ($fieldLayout->type === self::class) {
            $event->fields[] = NameField::class;
            $event->fields[] = DescriptionField::class;
            $event->fields[] = DataSourceSettingsField::class;
            //            $event->fields[] = VisualizationsSettingsField::class;
        }
    }

    protected static function defineTableAttributes(): array
    {
        $tableAttributes = [];
        // index or modal
        $context = Craft::$app->request->getParam('context');

        $tableAttributes['name'] = Craft::t('sprout-module-data-studio', 'Name');

        if ($context !== 'modal') {
            $tableAttributes['results'] = Craft::t('sprout-module-data-studio', 'View');
            $tableAttributes['download'] = Craft::t('sprout-module-data-studio', 'Export');
        }

        $tableAttributes['type'] = Craft::t('sprout-module-data-studio', 'Data Source');

        return $tableAttributes;
    }

    protected static function defineSearchableAttributes(): array
    {
        return [
            'name',
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        $tableAttributes = [];
        // index or modal
        $context = Craft::$app->request->getParam('context');

        $tableAttributes[] = 'name';

        if ($context !== 'modal') {
            $tableAttributes[] = 'results';
            $tableAttributes[] = 'download';
        }

        return $tableAttributes;
    }

    protected static function defineSortOptions(): array
    {
        return [
            'name' => Craft::t('sprout-module-data-studio', 'Name'),
            'type' => Craft::t('sprout-module-data-studio', 'Data Source'),
        ];
    }

    protected static function defineSources(string $context = null): array
    {
        $allLabel = 'All ' . static::pluralLowerDisplayName();

        $sources = [
            [
                'key' => '*',
                'label' => Craft::t('sprout-module-data-studio', $allLabel),
                'criteria' => [
                    'viewable' => true,
                ],
            ],
        ];

        $groups = self::getSourceGroups();

        if ($groups) {

            $sources[] = [
                'heading' => Craft::t('sprout-module-data-studio', 'Group'),
            ];

            foreach ($groups as $group) {
                $key = 'groupId:' . $group->id;

                $sources[] = [
                    'key' => $key,
                    'label' => Craft::t('sprout-module-data-studio', $group->name),
                    'data' => ['id' => $group->id],
                    'criteria' => [
                        'groupId' => $group->id,
                        'viewable' => true,
                    ],
                    'defaultSort' => ['name', 'asc'],
                ];
            }
        }

        return $sources;
    }

    protected static function defineActions(string $source = null): array
    {
        $self = new self();
        $actions = parent::defineActions($source);

        $currentUser = Craft::$app->getUser()->getIdentity();

        if (!$self->canSave($currentUser)) {
            return $actions;
        }

        // Edit
        $actions[] = Craft::$app->getElements()->createAction([
            'type' => Edit::class,
            'label' => Craft::t('sprout-module-data-studio', 'Edit Data Set'),
        ]);

        $actions[] = Craft::$app->getElements()->createAction([
            'type' => Delete::class,
            'hard' => false,
        ]);

        $actions[] = Restore::class;

        return $actions;
    }

    public function getSupportedSites(): array
    {
        // limit to just the one site this element is set to so that we don't propagate when saving
        return [$this->siteId];
    }

    public function __toString(): string
    {
        if ($this->nameFormat) {
            try {
                return $this->processNameFormat();
            } catch (InvalidValueException) {
                return Craft::t('sprout-module-data-studio', 'Invalid name format for data set: ' . $this->name);
            }
        }

        return $this->name;
    }

    public function getFieldLayout(): ?FieldLayout
    {
        $settings = DataStudioModule::getInstance()->getSettings();

        return $settings->getFieldLayout();
    }

    public function getDataSource(): DataSource
    {
        /** @var DataSource $dataSource */
        $dataSource = new $this->type($this->settings);
        $dataSource->setDataSet($this);

        return $dataSource;
    }

    public function processNameFormat(): string
    {
        $dataSource = $this->getDataSource();

        return Craft::$app->getView()->renderObjectTemplate($this->nameFormat, $dataSource->getSettings());
    }

    public function getVisualization(): ?Visualization
    {
        if (!$this->visualizationType) {
            return null;
        }

        // Only grab selected settings array if element post. Convert to array if from db.
        $visualizationSettings = $this->visualizationSettings[$this->visualizationType] ?? (
        !is_array($this->visualizationSettings)
            ? Json::decodeIfJson($this->visualizationSettings, true)
            : $this->visualizationSettings
        );

        $visualization = new $this->visualizationType($visualizationSettings);

        return $visualization;
    }

    public function beforeSave(bool $isNew): bool
    {
        if ($this->duplicateOf instanceof self) {
            $this->name .= ' 1';
        }

        return parent::beforeSave($isNew);
    }

    public function getSortColumnPosition(array $labels): ?int
    {
        // Get the position of our sort column for the Data Table settings
        $sortColumnPosition = array_search($this->sortColumn, $labels, true);

        if (!is_int($sortColumnPosition)) {
            $sortColumnPosition = null;
        }

        return $sortColumnPosition;
    }

    public function getSidebarHtml(bool $static): string
    {
        $groups = self::getSourceGroups();

        $groupOptions = [];
        $groupOptions[] = [
            'label' => Craft::t('sprout-module-data-studio', 'None'),
            'value' => '',
        ];

        foreach ($groups as $group) {
            $groupOptions[] = [
                'label' => Craft::t('sprout-module-data-studio', $group->name),
                'value' => $group->id,
            ];
        }

        $html = Craft::$app->getView()->renderTemplate('sprout-module-data-studio/_datasets/details.twig', [
            'dataSet' => $this,
            'groups' => $groupOptions,
            'static' => $static,
        ]);

        return $html . parent::getSidebarHtml($static);
    }

    public function cpEditUrl(): ?string
    {
        $path = UrlHelper::cpUrl('sprout/data-studio/edit/' . $this->id);

        $params = [];

        if (Craft::$app->getIsMultiSite()) {
            $params['site'] = $this->getSite()->handle;
        }

        return UrlHelper::cpUrl($path, $params);
    }

    public function getPostEditUrl(): ?string
    {
        return UrlHelper::cpUrl('sprout/data-studio');
    }

    public function getAdditionalButtons(): string
    {
        $html = Craft::$app->getView()->renderTemplate('sprout-module-core/_components/upgrade/button.twig', [
            'module' => DataStudioModule::getInstance(),
        ]);

        return $html . parent::getAdditionalButtons();
    }

    public function prepareEditScreen(Response $response, string $containerId): void
    {
        $crumbs = [
            [
                'label' => Craft::t('sprout-module-data-studio', 'Data Sets'),
                'url' => UrlHelper::url('sprout/data-studio'),
            ],
        ];

        /** @var Response|CpScreenResponseBehavior $response */
        $response->crumbs($crumbs);
    }

    public function tableAttributeHtml(string $attribute): string
    {
        if ($attribute === 'results') {
            $urlName = Craft::t('sprout-module-data-studio', 'View Report');
            $url = UrlHelper::cpUrl('sprout/data-studio/view/' . $this->id);

            return Html::tag('a', $urlName, [
                'class' => ['btn', 'small'],
                'href' => $url,
            ]);
        }

        if ($attribute === 'download') {

            $linkName = Craft::t('sprout-module-data-studio', 'Export');

            if (!DataStudioModule::isPro()) {
                return Html::tag('a', $linkName, [
                    'class' => ['btn', 'small', 'disabled'],
                    'href' => DataStudioModule::getUpgradeUrl(),
                    'title' => DataStudioModule::getUpgradeMessage(),
                    'style' => 'cursor: not-allowed;',
                    'disabled' => 'disabled',
                ]);
            }

            $url = UrlHelper::actionUrl('sprout-module-data-studio/data-set/export-data-set', [
                'dataSetId' => $this->id,
            ]);

            return Html::tag('a', $linkName, [
                'class' => ['btn', 'small'],
                'href' => $url,
            ]);
        }

        if ($attribute === 'type') {
            try {
                $dataSource = $this->getDataSource();
            } catch (Exception $e) {
                $message = Craft::t('sprout-module-data-studio', 'Data Source not found: {type}', [
                    'type' => $attribute,
                ]);

                Craft::error($e->getMessage(), __METHOD__);

                return Html::tag('span', $message, [
                    'class' => ['error'],
                ]);
            }

            return $dataSource::displayName();
        }

        return parent::tableAttributeHtml($attribute);
    }

    public function afterSave(bool $isNew): void
    {
        $dataSetRecord = $this->getDataSetRecord($isNew);
        $dataSetRecord->save(false);

        parent::afterSave($isNew);
    }

    public function canView(User $user): bool
    {
        if ($user->can(DataStudioModule::p('editDataSet'))) {
            return true;
        }

        return false;
    }

    public function canSave(User $user): bool
    {
        return $user->can(DataStudioModule::p('editDataSet:' . $this->type));
    }

    public function canDelete(User $user): bool
    {
        return $user->can(DataStudioModule::p('editDataSet:' . $this->type));
    }

    public function canDuplicate(User $user): bool
    {
        return $user->can(DataStudioModule::p('editDataSet:' . $this->type));
    }

    protected function metadata(): array
    {
        $dataSourceName = $this->getDataSource()::displayName();

        return [
            Craft::t('sprout-module-data-studio', 'Data Source') => $dataSourceName,
        ];
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['name', 'handle'], 'required', 'except' => self::SCENARIO_ESSENTIALS];
        $rules[] = [
            ['handle'],
            HandleValidator::class,
            'reservedWords' => [
                'id',
                'dateCreated',
                'dateUpdated',
                'uid',
                'title',
            ],
        ];
        $rules[] = [['description'], 'string', 'max' => 255];
        $rules[] = [['groupId'], 'safe'];
        $rules[] = [['nameFormat'], 'safe'];
        $rules[] = [['type'], 'safe'];
        $rules[] = [['sortOrder'], 'safe'];
        $rules[] = [['sortColumn'], 'safe'];
        $rules[] = [['delimiter'], 'safe'];
        $rules[] = [['allowHtml'], 'safe'];
        $rules[] = [['visualizationType'], 'safe'];
        $rules[] = [['visualizationSettings'], 'safe'];
        $rules[] = [['settings'], 'validateDataSourceSettings'];

        return $rules;
    }

    public function validateDataSourceSettings($attribute): void
    {
        $dataSource = $this->getDataSource();

        if (!$dataSource->validate()) {
            foreach ($dataSource->getErrors() as $errors) {
                foreach ($errors as $error) {
                    $this->addError($attribute, $error);
                }
            }
        }
    }

    private function getDataSetRecord(bool $isNew): ActiveRecord
    {
        if (!$isNew) {
            $dataSetRecord = DataSetRecord::findOne($this->id);
        } else {
            $dataSetRecord = new DataSetRecord();
            $dataSetRecord->id = $this->id;
        }

        $visualization = $this->getVisualization();

        $visualizationSettings = $visualization
            ? $visualization->getSettings()
            : [];

        $dataSetRecord->groupId = $this->groupId;
        $dataSetRecord->name = $this->name;
        $dataSetRecord->nameFormat = $this->nameFormat;
        $dataSetRecord->handle = $this->handle;
        $dataSetRecord->description = $this->description;
        $dataSetRecord->allowHtml = $this->allowHtml;
        $dataSetRecord->type = $this->type;
        $dataSetRecord->sortOrder = $this->sortOrder;
        $dataSetRecord->sortColumn = $this->sortColumn;
        $dataSetRecord->delimiter = $this->delimiter;
        $dataSetRecord->visualizationType = $this->visualizationType;
        $dataSetRecord->visualizationSettings = $visualizationSettings;
        $dataSetRecord->settings = $this->settings;
        $dataSetRecord->enabled = $this->enabled;

        return $dataSetRecord;
    }
}
