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

class IntegrationLogDataSource extends DataSource implements DateRangeInterface
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
        return 'forms-integration-log';
    }

    public static function displayName(): string
    {
        return Craft::t('sprout-module-forms', 'Form Integrations Log (Sprout)');
    }

    public function getDescription(): string
    {
        return Craft::t('sprout-module-forms', 'Query submission integrations results');
    }

    public function getResults(DataSetElement $dataSet): array
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $rows = [];

        $formId = $this->formId;

        $query = new Query();

        $formQuery = $query
            ->select([
                'id' => 'log.id',
                'dateCreated' => 'log.dateCreated',
                'dateUpdated' => 'log.dateUpdated',
                'submissionId' => 'log.submissionId',
                'integrationName' => 'integrations.name',
                'formName' => 'forms.name',
                'message' => 'log.message',
                'success' => 'log.success',
                'status' => 'log.status',
            ])
            ->from(['log' => SproutTable::FORM_INTEGRATIONS_LOG])
            ->innerJoin(['integrations' => SproutTable::FORM_INTEGRATIONS],
                '[[log.integrationId]] = [[integrations.id]]')
            ->innerJoin(['forms' => SproutTable::FORMS],
                '[[integrations.formId]] = [[forms.id]]');

        if ($formId != '*') {
            $formQuery->andWhere(['[[integrations.formId]]' => $formId]);
        }

        if ($startDate && $endDate) {
            $formQuery->andWhere('[[log.dateCreated]] > :startDate', [
                ':startDate' => $startDate->format('Y-m-d H:i:s'),
            ]);
            $formQuery->andWhere('[[log.dateCreated]] < :endDate', [
                ':endDate' => $endDate->format('Y-m-d H:i:s'),
            ]);
        }

        $results = $formQuery->all();

        if (!$results) {
            return $rows;
        }

        foreach ($results as $key => $result) {
            $message = $result['message'];

            if (strlen($result['message']) > 255) {
                $message = substr($result['message'], 0, 255) . ' ...';
            }

            $rows[$key]['id'] = $result['id'];
            $rows[$key]['submissionId'] = $result['submissionId'];
            $rows[$key]['formName'] = $result['formName'];
            $rows[$key]['integrationName'] = $result['integrationName'];
            $rows[$key]['message'] = $message;
            $rows[$key]['status'] = $result['status'];
            $rows[$key]['success'] = $result['success'] ? 'true' : 'false';
            $rows[$key]['dateCreated'] = $result['dateCreated'];
            $rows[$key]['dateUpdated'] = $result['dateUpdated'];
        }

        return $rows;
    }

    public function getSettingsHtml(array $settings = []): ?string
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

        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/datasources/IntegrationLogDataSource/settings', [
            'formOptions' => $formOptions,
            'defaultStartDate' => $defaultStartDate,
            'defaultEndDate' => $defaultEndDate,
            'dateRanges' => $dateRanges,
            'options' => $this->dataSet->getDataSource()->getSettings(),
        ]);
    }
}
