<?php

namespace BarrelStrength\Sprout\datastudio\components\audiences;

use BarrelStrength\Sprout\datastudio\components\elements\DataSetElement;
use BarrelStrength\Sprout\mailer\audience\AudienceType;
use Craft;
use craft\helpers\UrlHelper;

class DataSetAudienceType extends AudienceType
{
    /**
     * Stored as an array of a single ID
     */
    public ?array $dataSetIds = [];

    public function getDataSet(): ?DataSetElement
    {
        return DataSetElement::findOne($this->dataSetIds);
    }

    public static function displayName(): string
    {
        return Craft::t('sprout-module-data-studio', 'Data Set List');
    }

    public function getColumnAttributeHtml(): string
    {
        if (!$dataSet = $this->getDataSet()) {
            return '';
        }

        $resultsUrl = UrlHelper::cpUrl('sprout/data-studio/view/' . $dataSet->id);

        return '<a href="' . $resultsUrl . '" class="go">' .
            Craft::t('sprout-module-data-studio', 'Data Set') . '</a>';
    }

    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-data-studio/_components/audiences/settings.twig', [
            'audienceType' => $this,
            'dataSetElementType' => DataSetElement::class,
            'dataSet' => $this->getDataSet(),
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
