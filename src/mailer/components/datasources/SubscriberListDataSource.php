<?php

namespace BarrelStrength\Sprout\mailer\components\datasources;

use BarrelStrength\Sprout\datastudio\components\elements\DataSetElement;
use BarrelStrength\Sprout\datastudio\datasources\DataSource;
use BarrelStrength\Sprout\mailer\components\elements\audience\AudienceElementRecord;
use BarrelStrength\Sprout\mailer\db\SproutTable;
use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\records\User;

/**
 * @property string $defaultEmailColumn
 */
class SubscriberListDataSource extends DataSource
{
    public static function displayName(): string
    {
        return Craft::t('sprout-module-mailer', 'Subscriber List (Sprout Lists)');
    }

    public static function getHandle(): string
    {
        return 'subscriber-list-data-source';
    }

    public function getDescription(): string
    {
        return Craft::t('sprout-module-mailer', 'Create a Subscriber List with your Subscribers');
    }

    public function isEmailColumnEditable(): bool
    {
        return false;
    }

    public function getDefaultEmailColumn(): string
    {
        return 'email';
    }

    public function getResults(DataSetElement $dataSet): array
    {
        $reportSettings = $dataSet->getSettings();

        $listRecord = AudienceElementRecord::find()
            ->where([
                'id' => $reportSettings['subscriberListId'],
            ])
            ->one();

        /** @var User $subscriberRecords */
        $subscriberRecords = $listRecord->getSubscribers()->all();

        $subscribers = [];
        foreach ($subscriberRecords as $subscriberRecord) {
            $subscribers[] = $subscriberRecord->getAttributes();
        }

        return $subscribers;
    }

    public function getSettingsHtml(array $settings = []): ?string
    {
        $subscriberListOptions = (new Query())
            ->select([
                'label' => 'lists.name',
                'value' => 'lists.id',
            ])
            ->from(['lists' => SproutTable::AUDIENCES])
            ->leftJoin(['elements' => Table::ELEMENTS], '[[elements.id]] = [[lists.id]]')
            ->where([
                'elements.dateDeleted' => null,
            ])
            ->all();

        return Craft::$app->getView()->renderTemplate('sprout-module-mailer/_components/datasources/SubscriberList/settings', [
            'subscriberListOptions' => $subscriberListOptions,
        ]);
    }

    //    public function prepSettings(array $settings)
    //    {
    //        // Convert date strings to DateTime
    //        $settings['startDate'] = DateTimeHelper::toDateTime($settings['startDate']) ?: null;
    //        $settings['endDate'] = DateTimeHelper::toDateTime($settings['endDate']) ?: null;
    //
    //        return $settings;
    //    }
}
