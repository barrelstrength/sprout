<?php

namespace BarrelStrength\Sprout\forms\components\elements;

use BarrelStrength\Sprout\forms\captchas\Captcha;
use BarrelStrength\Sprout\forms\components\elements\actions\MarkAsDefaultStatus;
use BarrelStrength\Sprout\forms\components\elements\actions\MarkAsSpam;
use BarrelStrength\Sprout\forms\components\elements\conditions\SubmissionCondition;
use BarrelStrength\Sprout\forms\components\elements\db\SubmissionElementQuery;
use BarrelStrength\Sprout\forms\db\SproutTable;
use BarrelStrength\Sprout\forms\FormsModule;
use BarrelStrength\Sprout\forms\migrations\helpers\FormContentTableHelper;
use BarrelStrength\Sprout\forms\submissions\SubmissionRecord;
use BarrelStrength\Sprout\forms\submissions\SubmissionsSpamLog;
use BarrelStrength\Sprout\forms\submissions\SubmissionStatus;
use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\elements\actions\Delete;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\db\ElementQueryInterface;
use craft\elements\User;
use craft\errors\ElementNotFoundException;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\web\assets\conditionbuilder\ConditionBuilderAsset;
use craft\web\CpScreenResponseBehavior;
use yii\base\Exception;
use yii\web\Response;

class SubmissionElement extends Element
{
    public ?int $formId = null;

    public ?string $formHandle = null;

    public ?int $statusId = null;

    public ?string $statusHandle = null;

    public ?string $formName = null;

    public ?string $ipAddress = null;

    public ?string $referrer = null;

    public ?string $userAgent = null;

    /** @var Captcha[] $captchas */
    protected array $captchas = [];

    private ?FormElement $form = null;

    private array $integrationLogs = [];

    private ?array $conditionalResults = null;

    private ?array $submissionHiddenFields = null;

    public static function displayName(): string
    {
        return Craft::t('sprout-module-forms', 'Submission');
    }

    public static function lowerDisplayName(): string
    {
        return Craft::t('sprout-module-forms', 'submission');
    }

    public static function pluralDisplayName(): string
    {
        return Craft::t('sprout-module-forms', 'Submissions');
    }

    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('sprout-module-forms', 'submissions');
    }

    public static function refHandle(): ?string
    {
        return 'submissions';
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
        $statuses = FormsModule::getInstance()->submissionStatuses->getAllSubmissionStatuses();
        $statusArray = [];

        foreach ($statuses as $status) {
            $key = $status['handle'];
            $statusArray[$key] = [
                'label' => $status['name'],
                'color' => $status['color'],
            ];
        }

        return $statusArray;
    }

    /**
     * @return SubmissionElementQuery The newly created [[SubmissionElementQuery]] instance.
     */
    public static function find(): ElementQueryInterface
    {
        return new SubmissionElementQuery(static::class);
    }

    public static function createCondition(): ElementConditionInterface
    {
        return Craft::createObject(SubmissionCondition::class, [static::class]);
    }

    protected static function defineSources(string $context = null): array
    {
        $sources = [
            [
                'key' => '*',
                'label' => Craft::t('sprout-module-forms', 'All Submissions'),
                'defaultSort' => ['dateCreated', 'desc'],
            ],
        ];

        $sources[] = [
            'heading' => Craft::t('sprout-module-forms', 'Forms'),
        ];

        // Prepare the data for our sources sidebar
        $forms = FormsModule::getInstance()->forms->getAllForms();

        foreach ($forms as $form) {
            $saveData = FormsModule::getInstance()->submissions->isSaveDataEnabled($form);
            if ($saveData) {

                $sources[] = [
                    'key' => 'id:' . $form->id,
                    'label' => $form->name,
                    'data' => [
                        'formId' => $form->id,
                    ],
                    'criteria' => [
                        'formId' => $form->id,
                    ],
                    'defaultSort' => ['dateCreated', 'desc'],
                ];
            }
        }

        $settings = FormsModule::getInstance()->getSettings();

        $sources[] = [
            'heading' => Craft::t('sprout-module-forms', 'Misc'),
        ];

        if ($settings->saveSpamToDatabase) {
            $sources[] = [
                'key' => 'sproutFormsWithSpam',
                'label' => 'Spam',
                'criteria' => [
                    'status' => SubmissionStatus::SPAM_STATUS_HANDLE,
                ],
            ];
        }

        return $sources;
    }

    protected static function defineActions(string $source = null): array
    {
        $actions = parent::defineActions($source);

        $actions[] = MarkAsSpam::class;
        $actions[] = MarkAsDefaultStatus::class;
        $actions[] = Delete::class;

        return $actions;
    }

    protected static function defineSearchableAttributes(): array
    {
        return ['id', 'title', 'formName'];
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
        $attributes = [];
        $attributes['title'] = ['label' => Craft::t('sprout-module-forms', 'Title')];
        $attributes['formName'] = ['label' => Craft::t('sprout-module-forms', 'Form Name')];
        $attributes['dateCreated'] = ['label' => Craft::t('sprout-module-forms', 'Date Created')];
        $attributes['dateUpdated'] = ['label' => Craft::t('sprout-module-forms', 'Date Updated')];
        $attributes['id'] = ['label' => Craft::t('sprout-module-forms', 'ID')];
        $attributes['uid'] = ['label' => Craft::t('sprout-module-forms', 'UID')];

        foreach (Craft::$app->getElementSources()->getAvailableTableAttributes(FormElement::class) as $key => $field) {
            $customFields = explode(':', $key);
            if (count($customFields) > 1) {
                $fieldId = $customFields[1];
                $attributes['field:' . $fieldId] = ['label' => $field['label']];
            }
        }

        return $attributes;
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        return ['title', 'formName', 'dateCreated', 'dateUpdated'];
    }

    protected static function defineFieldLayouts(string $source): array
    {
        $fieldLayouts = [];

        if (preg_match('#^form:(.+)$#', $source, $matches) &&
            ($form = FormsModule::getInstance()->forms->getFormById($matches[1])) &&
            $fieldLayout = $form->getFieldLayout()) {
            $fieldLayouts[] = $fieldLayout;
        }

        return $fieldLayouts;
    }

    public function init(): void
    {
        parent::init();
        $this->setScenario(self::SCENARIO_LIVE);
    }

    /**
     * Returns the field context this element's content uses.
     *
     * @access protected
     */
    public function getFieldContext(): string
    {
        return 'sproutForms:' . $this->formId;
    }

    /**
     * Returns the name of the table this element's content is stored in.
     */
    public function getContentTable(): string
    {
        return FormContentTableHelper::getContentTable($this->getForm()->id);
    }

    public function cpEditUrl(): ?string
    {
        $path = UrlHelper::cpUrl('sprout/forms/submissions/edit/' . $this->id);

        $params = [];

        if (Craft::$app->getIsMultiSite()) {
            $params['site'] = $this->getSite()->handle;
        }

        return UrlHelper::cpUrl($path, $params);
    }

    public function getPostEditUrl(): ?string
    {
        return UrlHelper::cpUrl('sprout/forms/submissions');
    }

    public function canView(User $user): bool
    {
        $settings = FormsModule::getInstance()->getSettings();

        if (!$settings->enableSaveData) {
            return false;
        }

        return Craft::$app->getUser()->getIdentity()->can(FormsModule::p('viewSubmissions'));
    }

    public function canSave(User $user): bool
    {
        return Craft::$app->getUser()->getIdentity()->can(FormsModule::p('editSubmissions'));
    }

    public function canDelete(User $user): bool
    {
        return Craft::$app->getUser()->getIdentity()->can(FormsModule::p('editSubmissions'));
    }

    public function __toString(): string
    {
        // @todo - make this work like Entry Type Title Format
        // We currently run populateElementContent to get the Title to reflect
        // the Title Format setting. We should be able to do this in some other way.
        //Craft::$app->getContent()->populateElementContent($this);

        //$fieldsLocation = $this->request->getParam('fieldsLocation', 'fields');
        $this->setFieldValuesFromRequest('fields');

        return (string)$this->title;
    }

    public function prepareEditScreen(Response $response, string $containerId): void
    {
        $crumbs = [
            [
                'label' => Craft::t('sprout-module-forms', 'Submissions'),
                'url' => UrlHelper::url('sprout/forms/submissions'),
            ],
        ];

        /** @var Response|CpScreenResponseBehavior $response */
        $response->crumbs($crumbs);

        //Craft::$app->getView()->registerAssetBundle(ConditionBuilderAsset::class);
    }

    public function getFieldLayout(): ?FieldLayout
    {
        return $this->getForm()->getSubmissionFieldLayout();
    }

    public function getStatus(): ?string
    {
        $statusId = $this->statusId;

        return FormsModule::getInstance()->submissionStatuses->getSubmissionStatusById($statusId)->handle;
    }

    public function getSidebarHtml(bool $static): string
    {
        $html = parent::getSidebarHtml($static);

        $html .= Craft::$app->getView()->renderTemplate('sprout-module-forms/submissions/_sidebarIntegrations', [
            'submission' => $this
        ]);

        $html .= Craft::$app->getView()->renderTemplate('sprout-module-forms/submissions/_sidebarSpam', [
            'submission' => $this
        ]);

        return $html;
    }

    public function metadata(): array
    {
        return [
            Craft::t('sprout-module-forms', 'Form Name') => $this->getForm()->name,
        ];
    }

    public function afterSave(bool $isNew): void
    {
        if (!$isNew) {
            $record = SubmissionRecord::findOne($this->id);

            if (!$record instanceof SubmissionRecord) {
                throw new Exception('Invalid Submission ID: ' . $this->id);
            }
        } else {
            $record = new SubmissionRecord();
            $record->id = $this->id;
        }

        $record->formId = $this->formId;
        $record->statusId = $this->statusId;
        $record->title = $this->title;
        $record->ipAddress = $this->ipAddress;
        $record->userAgent = $this->userAgent;

        $record->save(false);

        parent::afterSave($isNew);
    }

    /**
     * Returns the fields associated with this form.
     */
    public function getFields(): array
    {
        return $this->getForm()->getFields();
    }

    /**
     * Returns the Form Element associated with this Submission
     * Due to soft delete, deleted forms leaves submissions with not forms
     */
    public function getForm(): FormElement
    {
        if ($this->form === null) {
            $form = FormsModule::getInstance()->forms->getFormById($this->formId);

            if (!$form instanceof ElementInterface) {
                throw new ElementNotFoundException('No Form exists with id ' . $this->formId);
            }

            $this->form = $form;
        }

        return $this->form;
    }

    public function getIntegrationLogs(): array
    {
        return $this->integrationLogs;
    }

    public function getIntegrationLog(): array
    {
        return FormsModule::getInstance()->formIntegrations->getIntegrationLogsBySubmissionId($this->id);
    }

    public function setConditionalLogicResults(array $conditionalResults): void
    {
        $this->conditionalResults = $conditionalResults;
    }

    public function getConditionalLogicResults(): ?array
    {
        return $this->conditionalResults;
    }

    public function getIsFieldHiddenByRule($fieldHandle): bool
    {
        $hiddenFields = $this->getHiddenFields();

        return in_array($fieldHandle, $hiddenFields, true);
    }

    public function setHiddenFields(?array $hiddenFields): void
    {
        $this->submissionHiddenFields = $hiddenFields;
    }

    public function getHiddenFields(): array
    {
        return $this->submissionHiddenFields ?? [];
    }

    public function getIsSpam(): bool
    {
        $status = $this->getStatus();

        return $status === SubmissionStatus::SPAM_STATUS_HANDLE;
    }

    public function addCaptcha(Captcha $captcha): void
    {
        $this->captchas[$captcha::class] = $captcha;
    }

    /**
     * @return Captcha[]
     */
    public function getCaptchas(): array
    {
        return $this->captchas;
    }

    public function hasCaptchaErrors(): bool
    {
        // When saving in the CP
        if ($this->captchas === null) {
            return false;
        }

        foreach ($this->captchas as $captcha) {
            if ($captcha->hasErrors()) {
                return true;
            }
        }

        return false;
    }

    public function getCaptchaErrors(): array
    {
        $errors = [];

        foreach ($this->captchas as $captcha) {
            if ($captcha->getErrors() !== []) {
                $errors['captchaErrors'][$captcha::class] = $captcha->getErrors('captchaErrors');
            }
        }

        return $errors;
    }

    public function getSavedCaptchaErrors(): array
    {
        $spamLogSubmissions = (new Query())
            ->select('*')
            ->from([SproutTable::FORM_SUBMISSIONS_SPAM_LOG])
            ->where(['submissionId' => $this->id])
            ->all();

        $captchaErrors = [];

        foreach ($spamLogSubmissions as $spamLogEntry) {
            $captchaErrors[] = new SubmissionsSpamLog($spamLogEntry);
        }

        return $captchaErrors;
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['formId'], 'required'];

        return $rules;
    }
}
