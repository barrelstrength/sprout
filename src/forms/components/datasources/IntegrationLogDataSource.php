<?php

namespace BarrelStrength\Sprout\forms\components\datasources;

use BarrelStrength\Sprout\datastudio\components\elements\DataSetElement;
use BarrelStrength\Sprout\datastudio\datasources\DataSource;
use BarrelStrength\Sprout\datastudio\datasources\DateRangeHelper;
use BarrelStrength\Sprout\forms\components\elements\FormElement;
use BarrelStrength\Sprout\forms\db\SproutTable;
use Craft;
use craft\db\Query;
use craft\helpers\DateTimeHelper;
use DateTime;

class IntegrationLogDataSource extends DataSource
{
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
        $startEndDate = $dataSet->getStartEndDate();
        $startDate = $startEndDate->getStartDate();
        $endDate = $startEndDate->getEndDate();

        $rows = [];

        $formId = $dataSet->getSetting('formId');

        $query = new Query();

        $formQuery = $query
            ->select('log.id id, log.dateCreated dateCreated, log.dateUpdated dateUpdated, log.submissionId submissionId, integrations.name integrationName, forms.name formName, log.message message, log.success success, log.status status')
            ->from(['log' => SproutTable::FORM_INTEGRATIONS_LOG])
            ->innerJoin(['integrations' => SproutTable::FORM_INTEGRATIONS], '[[log.integrationId]] = [[integrations.id]]')
            ->innerJoin(['forms' => SproutTable::FORMS], '[[integrations.formId]] = [[forms.id]]');

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
        $formOptions = [];
        /** @var FormElement[] $forms */
        $forms = FormElement::find()->limit(null)->orderBy('name')->all();

        if (empty($settings)) {
            $settings = $this->dataSet->getSettings();
        }

        $formOptions[] = ['label' => 'All', 'value' => '*'];

        foreach ($forms as $form) {
            $formOptions[] = [
                'label' => $form->name,
                'value' => $form->getId(),
            ];
        }

        $defaultStartDate = null;
        $defaultEndDate = null;

        if ($settings !== []) {
            if (isset($settings['startDate'])) {
                $startDateValue = (array)$settings['startDate'];

                $settings['startDate'] = DateTimeHelper::toDateTime($startDateValue);
            }

            if (isset($settings['endDate'])) {
                $endDateValue = (array)$settings['endDate'];

                $settings['endDate'] = DateTimeHelper::toDateTime($endDateValue);
            }
        }

        $dateRanges = DateRangeHelper::getDateRanges(false);

        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/datasources/IntegrationLogDataSource/settings', [
            'formOptions' => $formOptions,
            'defaultStartDate' => new DateTime($defaultStartDate),
            'defaultEndDate' => new DateTime($defaultEndDate),
            'dateRanges' => $dateRanges,
            'options' => $settings,
        ]);
    }

    public function prepSettings(array $settings): array
    {
        // Convert date strings to DateTime
        $settings['startDate'] = DateTimeHelper::toDateTime($settings['startDate']) ?: null;
        $settings['endDate'] = DateTimeHelper::toDateTime($settings['endDate']) ?: null;

        return $settings;
    }
}
