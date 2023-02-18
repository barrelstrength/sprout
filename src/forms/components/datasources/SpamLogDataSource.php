<?php

namespace BarrelStrength\Sprout\forms\components\datasources;

use BarrelStrength\Sprout\datastudio\components\elements\DataSetElement;
use BarrelStrength\Sprout\datastudio\datasources\DataSource;
use BarrelStrength\Sprout\datastudio\datasources\DateRangeHelper;
use BarrelStrength\Sprout\datastudio\datasources\DateRangeInterface;
use BarrelStrength\Sprout\datastudio\datasources\DateRangeTrait;
use BarrelStrength\Sprout\forms\components\elements\FormElement;
use BarrelStrength\Sprout\forms\db\SproutTable;
use Craft;
use craft\db\Query;

class SpamLogDataSource extends DataSource implements DateRangeInterface
{
    use DateRangeTrait;

    public ?int $formId = null;

    public function datetimeAttributes(): array
    {
        return [
            'startDate',
            'endDate',
        ];
    }

    public static function getHandle(): string
    {
        return 'forms-spam-log';
    }

    public static function displayName(): string
    {
        return Craft::t('sprout-module-forms', 'Form Spam Log (Sprout)');
    }

    public function getDescription(): string
    {
        return Craft::t('sprout-module-forms', 'Overview of spam submissions');
    }

    public function getResults(DataSetElement $dataSet): array
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $rows = [];

        $formId = $this->formId;

        $query = (new Query())
            ->select([
                'submissions_spam_log.id',
                'submissions_spam_log.submissionId',
                'submissions_spam_log.type',
                'submissions_spam_log.errors',
                'submissions_spam_log.dateCreated',
            ])
            ->from(SproutTable::FORM_SUBMISSIONS_SPAM_LOG . ' submissions_spam_log')
            ->innerJoin(
                ['submissions' => SproutTable::FORM_SUBMISSIONS],
                '[[submissions_spam_log.submissionId]] = [[submissions.id]]'
            )
            ->innerJoin(
                ['forms' => SproutTable::FORMS],
                '[[submissions.formId]] = [[forms.id]]'
            );

        if ($formId !== '*') {
            $query->andWhere(['[[submissions.formId]]' => $formId]);
        }

        if ($startDate && $endDate) {
            $query->andWhere('[[submissions_spam_log.dateCreated]] > :startDate', [
                ':startDate' => $startDate->format('Y-m-d H:i:s'),
            ]);
            $query->andWhere('[[submissions_spam_log.dateCreated]] < :endDate', [
                ':endDate' => $endDate->format('Y-m-d H:i:s'),
            ]);
        }

        $results = $query->all();

        if (!$results) {
            return $rows;
        }

        foreach ($results as $key => $result) {
            $captcha = new $result['type']();

            $rows[$key]['id'] = $result['id'];
            $rows[$key]['submissionId'] = $result['submissionId'];
            $rows[$key]['captchaName'] = $captcha->name;
            $rows[$key]['errors'] = $result['errors'];
            $rows[$key]['dateCreated'] = $result['dateCreated'];
        }

        return $rows;
    }

    public function getSettingsHtml(): ?string
    {
        /** @var FormElement[] $forms */
        $forms = FormElement::find()->limit(null)->orderBy('name')->all();

        $formOptions[] = ['label' => 'All', 'value' => '*'];

        foreach ($forms as $form) {
            $formOptions[] = [
                'label' => $form->name,
                'value' => $form->getId(),
            ];
        }

        $defaultStartDate = $this->getStartDate();
        $defaultEndDate = $this->getEndDate();

        $dateRanges = DateRangeHelper::getDateRanges(false);

        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/datasources/SpamLogDataSource/settings', [
            'formOptions' => $formOptions,
            'defaultStartDate' => $defaultStartDate,
            'defaultEndDate' => $defaultEndDate,
            'dateRanges' => $dateRanges,
            'options' => $this->dataSet->getDataSource()->getSettings(),
        ]);
    }
}
