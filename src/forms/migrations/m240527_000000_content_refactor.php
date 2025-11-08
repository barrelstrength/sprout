<?php

namespace BarrelStrength\Sprout\forms\migrations;

use BarrelStrength\Sprout\forms\FormsModule;
use BarrelStrength\Sprout\forms\formtypes\FormTypeHelper;
use craft\db\Query;
use craft\migrations\BaseContentRefactorMigration;

/**
 * @role temporary: Craft 4 => 5
 * @schema sprout-module-forms
 */
#[\Deprecated('tag:craftcms/cms:6.0', 'migration will be removed in Craft CMS 6.0')]
class m240527_000000_content_refactor extends BaseContentRefactorMigration
{
    public const FORMS_TABLE = '{{%sprout_forms}}';

    public const FORM_SUBMISSIONS_TABLE = '{{%sprout_form_submissions}}';

    public function safeUp(): void
    {
        $formTypes = FormTypeHelper::getFormTypes();

        foreach ($formTypes as $formType) {
            $this->updateElements(
                (new Query())
                    ->from(self::FORMS_TABLE)
                    ->where(['formTypeUid' => $formType->uid]),
                $formType->getFieldLayout(),
            );
        }

        $forms = FormsModule::getInstance()->forms->getAllForms();

        foreach ($forms as $form) {
            $this->updateElements(
                (new Query())->from(self::FORM_SUBMISSIONS_TABLE),
                $form->getSubmissionFieldLayout(),
            );
        }
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
