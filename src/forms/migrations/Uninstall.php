<?php

namespace BarrelStrength\Sprout\forms\migrations;

use BarrelStrength\Sprout\core\db\SproutTable as SproutTableCore;
use BarrelStrength\Sprout\forms\components\elements\FormElement;
use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\components\fields\FormsRelationField;
use BarrelStrength\Sprout\forms\components\fields\SubmissionsRelationField;
use BarrelStrength\Sprout\forms\db\SproutTable;
use BarrelStrength\Sprout\forms\FormsModule;
use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;

class Uninstall extends Migration
{
    public function safeDown(): void
    {
        $moduleSettingsKey = FormsModule::projectConfigPath();
        $coreModuleSettingsKey = FormsModule::projectConfigPath('modules.' . FormsModule::class);

        $formIds = (new Query())
            ->select('id')
            ->from([Table::ELEMENTS])
            ->where([
                'type' => FormElement::class,
            ])
            ->column();

        // Delete form via service layer to cleanup content tables, fields, etc.
        foreach ($formIds as $formId) {
            $form = Craft::$app->getElements()->getElementById($formId);
            if ($form instanceof FormElement) {
                FormsModule::getInstance()->forms->deleteForm($form);
            }
        }

        // Delete Submission and Form Elements (in case of orphans)
        $this->delete(Table::ELEMENTS, ['type' => FormElement::class]);
        $this->delete(Table::ELEMENTS, ['type' => SubmissionElement::class]);

        // Delete Fields
        $this->delete(Table::FIELDS, ['type' => FormsRelationField::class]);
        $this->delete(Table::FIELDS, ['type' => SubmissionsRelationField::class]);

        $this->delete(SproutTableCore::SOURCE_GROUPS, [
            'type' => FormElement::class,
        ]);

        // Order matters
        $this->dropTableIfExists(SproutTable::FORM_INTEGRATIONS_LOG);
        $this->dropTableIfExists(SproutTable::FORM_INTEGRATIONS);
        $this->dropTableIfExists(SproutTable::FORM_SUBMISSIONS_SPAM_LOG);
        $this->dropTableIfExists(SproutTable::FORM_SUBMISSIONS);
        $this->dropTableIfExists(SproutTable::FORM_SUBMISSIONS_STATUSES);
        $this->dropTableIfExists(SproutTable::FORMS);

        Craft::$app->getProjectConfig()->remove($moduleSettingsKey);
        Craft::$app->getProjectConfig()->remove($coreModuleSettingsKey);

        $this->delete(Table::USERPERMISSIONS, [
            'in', 'name', [
                FormsModule::p('accessModule', true),
                FormsModule::p('editForms', true),
                FormsModule::p('viewSubmissions', true),
                FormsModule::p('editSubmissions', true),
            ],
        ]);
    }
}
