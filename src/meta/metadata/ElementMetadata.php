<?php

namespace BarrelStrength\Sprout\meta\metadata;

use BarrelStrength\Sprout\meta\components\fields\ElementMetadataField;
use BarrelStrength\Sprout\meta\MetaModule;
use BarrelStrength\Sprout\uris\UrisModule;
use BarrelStrength\Sprout\uris\urlenabledsections\UrlEnabledSectionType;
use Craft;
use craft\base\Element;
use craft\base\Field;
use craft\db\Query;
use craft\db\Table;
use craft\events\FieldLayoutEvent;
use craft\models\FieldLayout;
use yii\base\Component;

class ElementMetadata extends Component
{
    /**
     * Returns the metadata for an Element's Element Metadata as a Metadata model
     */
    public function getRawMetadataFromElement(Element $element = null): array
    {
        if ($element === null) {
            return [];
        }

        $fieldHandle = $this->getElementMetadataFieldHandle($element);

        if (isset($element->{$fieldHandle})) {
            /** @var Metadata $metadata */
            $metadata = $element->{$fieldHandle};

            if ($metadata) {
                return $metadata->getRawData();
            }
        }

        return [];
    }

    /**
     * Returns the Field handle of the first Element Metadata field found in an Element Field Layout
     */
    public function getElementMetadataFieldHandle(Element $element = null): ?string
    {
        if ($element === null) {
            return null;
        }

        /** @var FieldLayout $fieldLayout */
        $fieldLayout = $element->getFieldLayout();
        $fields = $fieldLayout->getCustomFields();

        /**
         * Get our ElementMetadata Field
         *
         * @var Field $field
         */
        foreach ($fields as $field) {
            if (($field::class == ElementMetadataField::class) && isset($element->{$field->handle})) {
                return $field->handle;
            }
        }

        return null;
    }

    public function handleResaveElementsAfterFieldLayoutIsSaved(FieldLayoutEvent $event): void
    {
        if (!MetaModule::isEnabled()) {
            return;
        }

        $this->resaveElementsAfterFieldLayoutIsSaved($event->layout);
    }

    /**
     * Re-save Elements after a field layout or Element Metadata field is updated
     *
     * This is necessary when an Element Metadata field is added to a Field Layout
     * in a Section that Elements already exist, or if any changes are made to the
     * Element Metadata field type.
     */
    public function resaveElementsAfterFieldLayoutIsSaved(FieldLayout $fieldLayout): void
    {
        /**
         * The Field Layout event identifies the Element Type
         * that the layout is for:
         * Category, Entry, Commerce_Product, etc.
         */
        $elementType = $fieldLayout->type;
        $fieldLayoutFields = $fieldLayout->getCustomFields();
        $hasElementMetadataField = false;

        foreach ($fieldLayoutFields as $field) {
            if ($field instanceof ElementMetadataField) {
                $hasElementMetadataField = true;
                break;
            }
        }

        if ($hasElementMetadataField) {
            // Some Elements, like Commerce_Products
            // also need to save the related Variant field layout which returns as an array
            $this->resaveElementsByUrlEnabledSection($elementType, true, $fieldLayout);
        }
    }

    public function resaveElementsIfUsingElementMetadataField($fieldId): void
    {
        //Get all layoutIds where this field is used from craft_fieldlayoutfields.layoutId
        $fieldLayoutIds = (new Query())
            ->select('[[layoutId]]')
            ->from([Table::FIELDLAYOUTFIELDS])
            ->where(['[[fieldId]]' => $fieldId])
            ->all();

        $fieldLayoutIds = array_column($fieldLayoutIds, 'layoutId');

        $elementTypes = [];

        foreach ($fieldLayoutIds as $fieldLayoutId) {
            //Use that id to get the Element Type of each layout via the craft_fieldlayouts.type column
            $fieldLayout = (new Query())
                ->select('type')
                ->from([Table::FIELDLAYOUTS])
                ->where(['id' => $fieldLayoutId])
                ->one();

            $elementTypes[] = $fieldLayout['type'];
        }

        $elementTypes = array_unique($elementTypes);

        foreach ($elementTypes as $elementType) {
            $this->resaveElementsByUrlEnabledSection($elementType);
        }
    }

    public function getMetaBadgeInfo($settings): array
    {
        $targetSettings = [
            [
                'type' => 'optimizedTitleField',
                'value' => $settings['optimizedTitleField'],
                'badgeClass' => 'sprout-metatitle-info',
            ], [
                'type' => 'optimizedDescriptionField',
                'value' => $settings['optimizedDescriptionField'],
                'badgeClass' => 'sprout-metadescription-info',
            ], [
                'type' => 'optimizedImageField',
                'value' => $settings['optimizedImageField'],
                'badgeClass' => 'sprout-metaimage-info',
            ],
        ];

        $metaFieldHandles = [];
        foreach ($targetSettings as $targetSetting) {

            $handles = $this->getFieldHandles($targetSetting['value']);

            if (is_array($handles)) {
                foreach ($handles as $handle) {
                    if (isset($metaFieldHandles[$handle])) {
                        continue;
                    }

                    $metaFieldHandles[$handle] = [
                        'type' => $targetSetting['type'],
                        'handle' => $handle,
                        'badgeClass' => $targetSetting['badgeClass'],
                    ];
                }
            } else {
                if (isset($metaFieldHandles[$handles])) {
                    continue;
                }

                $metaFieldHandles[$handles] = [
                    'type' => $targetSetting['type'],
                    'handle' => $handles,
                    'badgeClass' => $targetSetting['badgeClass'],
                ];
            }
        }

        return $metaFieldHandles;
    }

    public function getFieldHandles($targetFieldSetting)
    {
        $targetField = $targetFieldSetting ?? null;

        if (!$targetField) {
            return [];
        }

        // Return the handle of the selected field and make into an array
        $existingFieldHandle = [$this->getExistingFieldHandle($targetField)];

        // Parse a custom setting and return an array or empty array
        $customSettingFieldHandles = $this->getCustomSettingFieldHandles($targetField);

        $fieldHandles = array_filter(array_merge($existingFieldHandle, $customSettingFieldHandles));

        if (count($fieldHandles) <= 0) {
            if ($targetField === 'elementTitle') {
                return 'title';
            }

            return $targetField;
        }

        return $fieldHandles;
    }

    public function getExistingFieldHandle($fieldId): string
    {
        // A number represents a specific image field selected
        if (!preg_match('#^\d+$#', $fieldId)) {
            return '';
        }

        /** @var Field $optimizedImageFieldModel */
        $optimizedImageFieldModel = Craft::$app->fields->getFieldById($fieldId);

        return $optimizedImageFieldModel->handle ?? '';
    }

    /**
     * Parses a custom Element Metadata field setting and returns tags used as an array of names
     */
    public function getCustomSettingFieldHandles($value): array
    {
        // If there are no dynamic tags, just return the template
        if (!str_contains($value, '{')) {
            return [];
        }

        /**
         *  {           - our pattern starts with an open bracket
         *  <space>?    - zero or one space
         *  (object\.)? - zero or one characters that spell "object."
         *  (?<handles> - begin capture pattern and name it 'handles'
         *  [a-zA-Z_]*  - any number of characters in Craft field handles
         *  )           - end capture pattern named 'handles'
         */
        preg_match_all('#{ ?(object\.)?(?<handles>[a-zA-Z_]*)#', $value, $matches);

        if (is_countable($matches['handles']) ? count($matches['handles']) : 0) {
            // Remove empty array items and make sure we only return each value once
            return array_filter(array_unique($matches['handles']));
        }

        return [];
    }

    public function getDescriptionLength(): int
    {
        $settings = MetaModule::getInstance()->getSettings();

        return $settings->maxMetaDescriptionLength ?: 160;
    }

    public function getMetadataFieldCount(): int|string
    {
        return (new Query())
            ->select(['id'])
            ->from([Table::FIELDS])
            ->where(['type' => ElementMetadataField::class])
            ->count();
    }

    /**
     * Triggers a Resave Elements job for each Url-Enabled Section with an Element Metadata field
     */
    protected function resaveElementsByUrlEnabledSection($elementType, bool $afterFieldLayout = false, FieldLayout $fieldLayout = null): bool
    {
        $urlEnabledSectionType = UrisModule::getInstance()->urlEnabledSections->getUrlEnabledSectionTypeByElementType($elementType);

        if (!$urlEnabledSectionType instanceof UrlEnabledSectionType) {
            return false;
        }

        if ($afterFieldLayout && !$urlEnabledSectionType->resaveElementsAfterFieldLayoutSaved()) {
            return false;
        }

        if ($urlEnabledSectionType) {
            foreach ($urlEnabledSectionType->urlEnabledSections as $urlEnabledSection) {
                if ($afterFieldLayout && $fieldLayout !== null) {
                    if ($urlEnabledSection->hasFieldLayoutId($fieldLayout->id)) {
                        // Need to figure out where to grab sectionId, entryTypeId, categoryGroupId, etc.
                        $elementGroupId = $urlEnabledSection->id;
                        $urlEnabledSectionType->resaveElements($elementGroupId);

                        break;
                    }
                } elseif ($urlEnabledSection->hasElementMetadataField(false)) {
                    $elementGroupId = $urlEnabledSection->id;
                    $urlEnabledSectionType->resaveElements($elementGroupId);
                }
            }
        }

        return true;
    }
}
