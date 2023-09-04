<?php

namespace BarrelStrength\Sprout\forms\components\elements\db;

use BarrelStrength\Sprout\forms\db\SproutTable;
use BarrelStrength\Sprout\forms\FormsModule;
use craft\db\Table;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;

class FormElementQuery extends ElementQuery
{
    public ?string $name = null;

    public ?string $handle = null;

    public ?string $submissionFieldLayoutId = null;

    public ?string $formTypeUid = null;

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

    public function formTypeUid(string $value): FormElementQuery
    {
        $this->formTypeUid = $value;

        return $this;
    }

    protected function beforePrepare(): bool
    {
        $this->joinElementTable('sprout_forms');

        $this->query->select([
            'sprout_forms.id',
            'sprout_forms.submissionFieldLayoutId',
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
            'sprout_forms.formTypeUid',
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

        if ($this->formTypeUid) {
            $this->subQuery->andWhere(Db::parseParam(
                'sprout_forms.formTypeUid', $this->formTypeUid
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
