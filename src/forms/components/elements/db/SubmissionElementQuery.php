<?php

namespace BarrelStrength\Sprout\forms\components\elements\db;

use BarrelStrength\Sprout\forms\components\elements\FormElement;
use BarrelStrength\Sprout\forms\db\SproutTable;
use BarrelStrength\Sprout\forms\FormsModule;
use BarrelStrength\Sprout\forms\submissions\SubmissionStatus;
use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;

class SubmissionElementQuery extends ElementQuery
{
    public ?int $statusId = null;

    public string $ipAddress = '';

    public string $userAgent = '';

    public ?int $formId = null;

    public string $formHandle = '';

    public string $formName = '';

    public array|string|null $status = [];

    private bool $excludeSpam = true;

    public function __construct(string $elementType, array $config = [])
    {
        // Default orderBy
        if (!isset($config['orderBy'])) {
            $config['orderBy'] = 'sprout_form_submissions.id';
        }

        parent::__construct($elementType, $config);
    }

    /**
     * Sets the [[statusId]] property.
     *
     * @return static self reference
     */
    public function statusId(int $value): SubmissionElementQuery
    {
        $this->statusId = $value;

        return $this;
    }

    /**
     * Sets the [[formId]] property.
     *
     * @return static self reference
     */
    public function formId(int $value): SubmissionElementQuery
    {
        $this->formId = $value;

        return $this;
    }

    /**
     * Sets the [[formHandle]] property.
     *
     * @return static self reference
     */
    public function formHandle(string $value): SubmissionElementQuery
    {
        $this->formHandle = $value;
        $form = FormsModule::getInstance()->forms->getFormByHandle($value);
        // To add support to filtering we need to have the formId set.
        if ($form !== null) {
            $this->formId = $form->id;
        }

        return $this;
    }

    /**
     * Sets the [[formName]] property.
     *
     * @return static self reference
     */
    public function formName(string $value): SubmissionElementQuery
    {
        $this->formName = $value;

        return $this;
    }

    protected function beforePrepare(): bool
    {
        $this->joinElementTable('sprout_form_submissions');

        // Figure out which content table to use
        $this->contentTable = null;

        if (!$this->formId && $this->id) {
            $formIds = (new Query())
                ->select(['formId'])
                ->distinct()
                ->from([SproutTable::FORM_SUBMISSIONS])
                ->where(Db::parseParam('id', $this->id))
                ->column();

            $this->formId = count($formIds) === 1 ? $formIds[0] : $formIds;
        }

        if ($this->formId && is_numeric($this->formId)) {
            /** @var FormElement $form */
            $form = FormsModule::getInstance()->forms->getFormById($this->formId);

            if ($form) {
                $this->contentTable = $form->getContentTable();
            }
        }

        $this->query->select([
            'sprout_form_submissions.statusId',
            'sprout_form_submissions.formId',
            'sprout_form_submissions.ipAddress',
            'sprout_form_submissions.userAgent',
            'sprout_form_submissions.dateCreated',
            'sprout_form_submissions.dateUpdated',
            'sprout_form_submissions.uid',
            'sprout_forms.name as formName',
            'sprout_forms.handle as formHandle',
            'sprout_form_submissions_statuses.handle as statusHandle',
        ]);

        $this->query->innerJoin(['sprout_forms' => SproutTable::FORMS], '[[sprout_forms.id]] = [[sprout_form_submissions.formId]]');
        $this->query->innerJoin(['sprout_form_submissions_statuses' => SproutTable::FORM_SUBMISSIONS_STATUSES], '[[sprout_form_submissions_statuses.id]] = [[sprout_form_submissions.statusId]]');

        $this->query->andWhere(Db::parseParam(
            '[[sprout_forms.saveData]]', true
        ));

        $this->subQuery->innerJoin(['sprout_forms' => SproutTable::FORMS], '[[sprout_forms.id]] = [[sprout_form_submissions.formId]]');
        $this->subQuery->innerJoin(['sprout_form_submissions_statuses' => SproutTable::FORM_SUBMISSIONS_STATUSES], '[[sprout_form_submissions_statuses.id]] = [[sprout_form_submissions.statusId]]');

        if ($this->formId) {
            $this->subQuery->andWhere(Db::parseParam(
                'sprout_form_submissions.formId', $this->formId
            ));
        }

        if ($this->id) {
            $this->subQuery->andWhere(Db::parseParam(
                'sprout_form_submissions.id', $this->id
            ));
        }

        if ($this->formHandle) {
            $this->query->andWhere(Db::parseParam(
                'sprout_forms.handle', $this->formHandle
            ));
        }

        if ($this->formName) {
            $this->query->andWhere(Db::parseParam(
                'sprout_forms.name', $this->formName
            ));
        }

        if ($this->statusId) {
            $this->subQuery->andWhere(Db::parseParam(
                'sprout_form_submissions.statusId', $this->statusId
            ));
        }

        $spamStatusId = FormsModule::getInstance()->submissionStatuses->getSpamStatusId();

        // If and ID is being requested directly OR the spam status ID OR
        // the spam status handle is explicitly provided, override the include spam flag
        if ($this->id || $this->statusId === $spamStatusId || $this->status === SubmissionStatus::SPAM_STATUS_HANDLE) {
            $this->excludeSpam = false;
        }

        if ($this->excludeSpam) {
            $this->subQuery->andWhere(Db::parseParam(
                'sprout_form_submissions.statusId', $spamStatusId, '!='
            ));
        }

        return parent::beforePrepare();
    }

    protected function statusCondition(string $status): mixed
    {
        return Db::parseParam('sprout_form_submissions_statuses.handle', $status);
    }

    protected function customFields(): array
    {
        // This method won't get called if $this->formId isn't set to a single int
        /** @var FormElement $form */
        $form = FormsModule::getInstance()->forms->getFormById($this->formId);

        return $form->getFields();
    }
}
