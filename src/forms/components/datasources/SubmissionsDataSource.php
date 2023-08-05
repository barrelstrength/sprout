<?php

namespace BarrelStrength\Sprout\forms\components\datasources;

use BarrelStrength\Sprout\datastudio\components\elements\DataSetElement;
use BarrelStrength\Sprout\datastudio\datasources\DataSource;
use BarrelStrength\Sprout\datastudio\datasources\DateRangeHelper;
use BarrelStrength\Sprout\datastudio\datasources\DateRangeInterface;
use BarrelStrength\Sprout\datastudio\datasources\DateRangeTrait;
use BarrelStrength\Sprout\forms\components\elements\FormElement;
use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\components\formfields\NameFormFieldData;
use BarrelStrength\Sprout\forms\components\formfields\PhoneFormFieldData;
use BarrelStrength\Sprout\forms\db\SproutTable;
use BarrelStrength\Sprout\forms\fields\address\Address as AddressModel;
use BarrelStrength\Sprout\forms\FormsModule;
use Craft;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\db\Table;
use craft\elements\db\ElementQueryInterface;
use craft\fields\data\MultiOptionsFieldData;
use craft\fields\data\SingleOptionFieldData;
use craft\helpers\Json;

class SubmissionsDataSource extends DataSource implements DateRangeInterface
{
    use DateRangeTrait;

    public ?int $formId = null;

    public array $submissionStatusIds = [];

    public function datetimeAttributes(): array
    {
        return [
            'startDate',
            'endDate',
        ];
    }

    public static function displayName(): string
    {
        return Craft::t('sprout-module-forms', 'Submissions (Sprout)');
    }

    public function getDescription(): string
    {
        return Craft::t('sprout-module-forms', 'Query submissions');
    }

    public function getResults(DataSetElement $dataSet): array
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $rows = [];

        $formId = $this->formId;
        $form = FormsModule::getInstance()->forms->getFormById($formId);

        $submissionStatusIds = $this->submissionStatusIds;

        if (!$form instanceof ElementInterface) {
            return [];
        }

        $contentTable = $form->contentTable;

        $query = new Query();

        $formQuery = $query
            ->select([
                'elementId' => 'elements.id',
                'siteId' => 'elements_sites.siteId',
                'title' => 'formcontenttable.title',
                'submissionStatusName' => 'submissionstatuses.name',
                'ipAddress' => '[[submissions.ipAddress',
                'referrer' => 'submissions.referrer',
                'userAgent' => 'submissions.userAgent',
                'dateCreated' => 'submissions.dateCreated',
                'dateUpdated' => 'submissions.dateUpdated',
            ])
            ->from(['formcontenttable' => $contentTable])
            ->innerJoin(['elements' => Table::ELEMENTS],
                '[[formcontenttable.elementId]] = [[elements.id]]')
            ->innerJoin(['elements_sites' => Table::ELEMENTS_SITES],
                '[[elements_sites.elementId]] = [[elements.id]]')
            ->innerJoin(['submissions' => SproutTable::FORM_SUBMISSIONS],
                '[[submissions.id]] = [[elements.id]]')
            ->innerJoin(['submissionstatuses' => SproutTable::FORM_SUBMISSIONS_STATUSES],
                '[[submissions.statusId]] = [[submissionstatuses.id]]')
            ->where(['elements.dateDeleted' => null]);

        if ($startDate && $endDate) {
            $formQuery->andWhere('[[formcontenttable.dateCreated]] > :startDate', [
                ':startDate' => $startDate->format('Y-m-d H:i:s'),
            ]);
            $formQuery->andWhere('[[formcontenttable.dateCreated]] < :endDate', [
                ':endDate' => $endDate->format('Y-m-d H:i:s'),
            ]);
        }

        if (is_countable($submissionStatusIds) ? count($submissionStatusIds) : 0) {
            $formQuery->andWhere(['submissions.statusId' => $submissionStatusIds]);
        }

        $results = $formQuery->all();

        if (!$results) {
            return $rows;
        }

        foreach ($results as $key => $result) {
            $elementId = $result['elementId'];
            $rows[$key]['elementId'] = $elementId;
            $rows[$key]['siteId'] = $result['siteId'];
            $rows[$key]['title'] = $result['title'];
            $rows[$key]['status'] = $result['submissionStatusName'];
            $rows[$key]['ipAddress'] = $result['ipAddress'];
            $rows[$key]['referrer'] = $result['referrer'];
            $rows[$key]['userAgent'] = $result['userAgent'];
            $rows[$key]['dateCreated'] = $result['dateCreated'];
            $rows[$key]['dateUpdated'] = $result['dateUpdated'];

            $submission = Craft::$app->getElements()->getElementById($elementId, SubmissionElement::class);

            $fields = $submission === null ? [] : $submission->getFieldValues();

            if ((is_countable($fields) ? count($fields) : 0) <= 0) {
                continue;
            }

            foreach ($fields as $handle => $field) {
                if ($field instanceof ElementQueryInterface) {
                    $submissions = $field->all();
                    $titles = [];
                    foreach ($submissions as $submission) {
                        $titles[] = '"' . $submission->title . '"';
                    }

                    $value = '';
                    if (!empty($titles)) {
                        $value = implode(', ', $titles);
                    }
                } elseif ($field instanceof SingleOptionFieldData) {
                    $options = $field->getOptions();
                    foreach ($options as $option) {
                        if ($option->selected) {
                            $value = $option->value;
                            break;
                        }
                    }
                } elseif ($field instanceof MultiOptionsFieldData) {
                    $options = $field->getOptions();
                    $selectedOptions = [];
                    foreach ($options as $option) {
                        if ($option->selected) {
                            $selectedOptions[] = '"' . $option->value . '"';
                        }
                    }

                    $value = '';
                    if ($selectedOptions !== []) {
                        $value = implode(', ', $selectedOptions);
                    }
                } elseif ($field instanceof AddressModel) {
                    $addressWithSpanTags = FormsModule::getInstance()->addressFormatter->getAddressDisplayHtml($field);
                    $value = strip_tags($addressWithSpanTags);
                } elseif ($field instanceof NameFormFieldData) {
                    $value = $field->getFullName();
                } elseif ($field instanceof PhoneFormFieldData) {
                    $value = $field->getInternational();
                } elseif (is_array($field)) {
                    $value = Json::encode($field);
                } elseif (is_string($field) || $field === null) {
                    $value = $field;
                } else {
                    $value = Craft::t('sprout-module-forms', 'Unsupported Field');
                }

                $fieldHandleKey = 'field_' . $handle;
                $rows[$key][$fieldHandleKey] = $value ?? null;
            }
        }

        return $rows;
    }

    public function getSettingsHtml(): ?string
    {
        /** @var FormElement[] $forms */
        $forms = FormElement::find()->limit(null)->orderBy('name')->all();

        $formOptions = [];

        foreach ($forms as $form) {
            $formOptions[] = [
                'label' => $form->name,
                'value' => $form->getId(),
            ];
        }

        $defaultStartDate = $this->getStartDate();
        $defaultEndDate = $this->getEndDate();

        $dateRanges = DateRangeHelper::getDateRanges(false);

        $submissionStatusOptions = [];
        $defaultSelectedSubmissionStatuses = [];

        $submissionStatuses = FormsModule::getInstance()->submissionStatuses->getAllSubmissionStatuses();
        $spamStatusId = FormsModule::getInstance()->submissionStatuses->getSpamStatusId();

        foreach ($submissionStatuses as $submissionStatus) {
            $submissionStatusOptions[$submissionStatus->id]['label'] = $submissionStatus->name;
            $submissionStatusOptions[$submissionStatus->id]['value'] = $submissionStatus->id;

            if ($submissionStatus->id !== $spamStatusId) {
                $defaultSelectedSubmissionStatuses[] = $submissionStatus->id;
            }
        }

        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/datasources/SubmissionsDataSource/settings', [
            'formOptions' => $formOptions,
            'defaultStartDate' => $defaultStartDate,
            'defaultEndDate' => $defaultEndDate,
            'dateRanges' => $dateRanges,
            'options' => $this->dataSet->getDataSource()->getSettings(),
            'submissionStatusOptions' => $submissionStatusOptions,
            'defaultSelectedSubmissionStatuses' => $defaultSelectedSubmissionStatuses,
        ]);
    }
}
