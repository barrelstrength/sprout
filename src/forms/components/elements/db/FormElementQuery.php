<?php

namespace BarrelStrength\Sprout\forms\components\elements\db;

use BarrelStrength\Sprout\forms\db\SproutTable;
use BarrelStrength\Sprout\forms\FormsModule;
use craft\db\Query;
use craft\db\Table;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;

class FormElementQuery extends ElementQuery
{
    public array|int|null $groupId = null;

    public ?string $name = null;

    public ?string $handle = null;

    public ?string $submissionFieldLayoutId = null;

    public ?string $formThemeUid = null;

    //    public string $oldHandle;
    //
    //    public string $titleFormat;
    //
    //    public bool $displaySectionTitles = false;
    //
    //    public string $redirectUri;
    //
    //    public string $submissionMethod;
    //
    //    public string $errorDisplayMethod;
    //
    //    public string $messageOnSuccess;
    //
    //    public string $messageOnError;
    //
    //    public string $submitButtonText;
    //
    //    public bool $saveData = false;
    //
    //    public bool $enableCaptchas = false;

    public int $totalSubmissions = 0;

    public int $numberOfFields = 0;

    public function __construct(string $elementType, array $config = [])
    {
        // Default orderBy
        if (!isset($config['orderBy'])) {
            $config['orderBy'] = 'sprout_forms.name';
        }

        parent::__construct($elementType, $config);
    }

    public function group($value): FormElementQuery
    {
        if ($value instanceof FormGroup) {
            $this->groupId = $value->id;
        } elseif ($value !== null) {
            $this->groupId = (new Query())
                ->select(['id'])
                ->from([SproutTable::FORM_GROUPS])
                ->where(Db::parseParam('name', $value))
                ->column();
        } else {
            $this->groupId = null;
        }

        return $this;
    }

    /**
     * Sets the [[groupId]] property.
     *
     * @param int|int[]|null $value The property value
     *
     * @return static self reference
     */
    public function groupId($value): FormElementQuery
    {
        $this->groupId = $value;

        return $this;
    }

    public function name(string $value): FormElementQuery
    {
        $this->name = $value;

        return $this;
    }

    public function handle(string $value): FormElementQuery
    {
        $this->handle = $value;

        return $this;
    }

    public function submissionFieldLayoutId(string $value): FormElementQuery
    {
        $this->submissionFieldLayoutId = $value;

        return $this;
    }

    public function formThemeUid(string $value): FormElementQuery
    {
        $this->formThemeUid = $value;

        return $this;
    }

    protected function beforePrepare(): bool
    {
        // See if 'group' was set to an invalid handle
        if ($this->groupId === []) {
            return false;
        }

        $this->joinElementTable('sprout_forms');

        $this->query->select([
            'sprout_forms.groupId',
            'sprout_forms.id',
            'sprout_forms.submissionFieldLayoutId',
            'sprout_forms.groupId',
            'sprout_forms.name',
            'sprout_forms.handle',
            'sprout_forms.titleFormat',
            'sprout_forms.displaySectionTitles',
            'sprout_forms.redirectUri',
            'sprout_forms.saveData',
            'sprout_forms.submissionMethod',
            'sprout_forms.errorDisplayMethod',
            'sprout_forms.messageOnSuccess',
            'sprout_forms.messageOnError',
            'sprout_forms.submitButtonText',
            'sprout_forms.formThemeUid',
            'sprout_forms.enableCaptchas',
        ]);

        if ($this->totalSubmissions) {
            $this->query->addSelect('COUNT(submissions.id) totalSubmissions');
            $this->query->leftJoin(['submissions' => SproutTable::FORM_SUBMISSIONS], '[[submissions.formId]] = [[sprout_forms.id]]');
        }

        if ($this->numberOfFields) {
            $this->query->addSelect('COUNT(fields.id) numberOfFields');
            $this->query->leftJoin(Table::FIELDLAYOUTFIELDS . ' fields', '[[fields.layoutId]] = [[sprout_forms.submissionFieldLayoutId]]');
        }

        if ($this->submissionFieldLayoutId) {
            $this->subQuery->andWhere(Db::parseParam(
                'sprout_forms.submissionFieldLayoutId', $this->submissionFieldLayoutId
            ));
        }

        if ($this->formThemeUid) {
            $this->subQuery->andWhere(Db::parseParam(
                'sprout_forms.formThemeUid', $this->formThemeUid
            ));
        }

        if ($this->groupId) {
            $this->subQuery->andWhere(Db::parseParam(
                'sprout_forms.groupId', $this->groupId
            ));
        }

        if ($this->handle) {
            $this->subQuery->andWhere(Db::parseParam(
                'sprout_forms.handle', $this->handle
            ));
        }

        if ($this->name) {
            $this->subQuery->andWhere(Db::parseParam(
                'sprout_forms.name', $this->name
            ));
        }

        // Limit Sprout Forms Lite to a single form
        if (!FormsModule::isPro()) {
            $this->query->limit(1);
        }

        return parent::beforePrepare();
    }
}
