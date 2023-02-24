<?php

namespace BarrelStrength\Sprout\datastudio\components\audiences;

use BarrelStrength\Sprout\datastudio\components\elements\DataSetElement;
use BarrelStrength\Sprout\mailer\audience\AudienceType;
use Craft;

class DataSetAudienceType extends AudienceType
{
    public ?array $dataSetIds = [];

    public static function displayName(): string
    {
        return Craft::t('sprout-module-mailer', 'Data Set List');
    }

    public function getHandle(): string
    {
        return 'data-set';
    }

    public function getSettingsHtml(): ?string
    {
        $dataSet = DataSetElement::findOne($this->dataSetIds);

        return Craft::$app->getView()->renderTemplate('sprout-module-data-studio/_components/audiences/settings', [
            'audienceType' => $this,
            'dataSetElementType' => DataSetElement::class,
            'dataSet' => $dataSet,
        ]);
    }

    public function getRecipients(): array
    {
        // Get Data Set Results
        // Assign MailingListRecipient
        // return MailingListRecipient[]
        return [];
    }
}
