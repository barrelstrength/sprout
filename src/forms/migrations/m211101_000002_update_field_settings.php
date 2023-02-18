<?php

/** @noinspection ClassConstantCanBeUsedInspection */

namespace BarrelStrength\Sprout\forms\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Json;

class m211101_000002_update_field_settings extends Migration
{
    public function safeUp(): void
    {
        $this->removeRetiredFieldTypes();

        // @todo - Look at every field setting that exist and make sure
        // it's the data type we expect it to be
        // Should we make a helper to audit these?
        // https://github.com/barrelstrength/craft-sprout-forms/issues/490

        $this->cleanUpAddressFieldStuff();
        $this->cleanUpPredefinedFieldStuff();
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }

    public function removeRetiredFieldTypes(): void
    {
        // @todo
        $notesFieldIds = (new Query())
            ->select(['id'])
            ->from('{{%fields}}')
            ->where(['type' => 'barrelstrength\sproutfields\fields\Notes'])
            ->column();

        $notesFieldUids = (new Query())
            ->select(['uid'])
            ->from('{{%fields}}')
            ->where(['type' => 'barrelstrength\sproutfields\fields\Notes'])
            ->column();

        $fieldLayoutIds = (new Query())
            ->select(['layoutId'])
            ->from('{{%fieldlayoutfields}}')
            ->where(['in', 'fieldId', $notesFieldIds])
            ->distinct()
            ->column();

        $layouts = (new Query())
            ->select([
                'entrytypes.uid AS entryTypeUid',
                'fieldlayouts.uid AS fieldLayoutUid',
            ])
            ->from(['entrytypes' => '{{%entrytypes}}'])
            ->innerJoin(['fieldlayouts' => '{{%fieldlayouts}}'],
                '[[fieldlayouts.id]] = [[entrytypes.fieldLayoutId]]')
            ->where(['in', 'fieldlayouts.id', $fieldLayoutIds])
            ->all();

        $projectConfig = Craft::$app->getProjectConfig();

        // Loop through all the layouts that have Notes fields
        foreach ($layouts as $layout) {
            if (!isset($layout['entryTypeUid'], $layout['fieldLayoutUid'])) {
                continue;
            }

            $tabsPath = 'entryTypes.' . $layout['entryTypeUid'] . '.fieldLayouts.' . $layout['fieldLayoutUid'] . '.tabs';

            $tabs = $projectConfig->get($tabsPath);
            $fields = $projectConfig->get('fields');

            $newTabs = [];

            // Loop through all the tabs of the layout
            foreach ($tabs as $tab) {

                // Loop through each field or UI element found in the layout
                foreach ($tab['elements'] as $key => $element) {

                    // Get UID of the Notes field we're looking for.
                    if (isset($element['fieldUid']) &&
                        in_array($element['fieldUid'], $notesFieldUids, true)) {
                        $field = $fields[$element['fieldUid']] ?? null;

                        if (!$field) {
                            continue;
                        }

                        $oldStyle = $field['settings']['style'] ?? null;
                        $notes = $field['settings']['notes'] ?? null;

                        if ($oldStyle === 'warningDocumentation' || $oldStyle === 'dangerDocumentation') {
                            $newStyle = 'warning';
                            $newType = 'craft\fieldlayoutelements\Warning';
                        } else {
                            $newStyle = 'tip';
                            $newType = 'craft\fieldlayoutelements\Tip';
                        }

                        $tab['elements'][$key] = [
                            'style' => $newStyle,
                            'tip' => $notes ?? '',
                            'type' => $newType,
                        ];
                    }
                }

                $newTabs[] = $tab;

                // Then save the project config remove field with the UID in question.
                $projectConfig->set($tabsPath, $newTabs);
            }
        }

        // Log previous data for user to refer to if needed.
        $this->delete(Table::FIELDS, ['id' => $notesFieldIds]);
    }

    public function cleanUpAddressFieldStuff(): void
    {
        $fields = (new Query())
            ->select(['id', 'settings'])
            ->from('{{%fields}}')
            ->where(['type' => 'barrelstrength\sproutfields\fields\Address'])
            ->orWhere(['type' => 'barrelstrength\sproutforms\fields\formfields\Address'])
            ->all();

        // Remove deprecated attributes and resave settings
        foreach ($fields as $field) {
            $id = $field['id'];
            $settings = Json::decode($field['settings']);

            unset(
                $settings['addressHelper'],
                $settings['hideCountryDropdown'],
            );

            $this->update('{{%fields}}', [
                'settings' => Json::encode($settings),
            ], ['id' => $id], [], false);
        }
    }

    public function cleanUpPredefinedFieldStuff(): void
    {
        $fields = (new Query())
            ->select(['id', 'settings'])
            ->from('{{%fields}}')
            ->where(['type' => 'barrelstrength\sproutfields\fields\Predefined'])
            ->orWhere(['type' => 'barrelstrength\sproutfields\fields\PredefinedDate'])
            ->all();

        // Remove deprecated attributes and resave settings
        foreach ($fields as $field) {
            $id = $field['id'];
            $settings = Json::decode($field['settings']);

            unset(
                $settings['contentColumnType'],
                $settings['outputTextarea'],
            );

            $this->update('{{%fields}}', [
                'settings' => Json::encode($settings),
            ], ['id' => $id], [], false);
        }
    }
}
