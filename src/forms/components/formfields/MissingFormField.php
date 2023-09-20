<?php

namespace BarrelStrength\Sprout\forms\components\formfields;

use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\formfields\FormFieldInterface;
use BarrelStrength\Sprout\forms\formfields\FormFieldTrait;
use BarrelStrength\Sprout\forms\formfields\GroupLabel;
use Craft;
use craft\base\ElementInterface;
use craft\fields\Dropdown as CraftDropdown;
use craft\fields\MissingField;
use craft\fields\PlainText as CraftPlainText;
use craft\helpers\Db;
use LitEmoji\LitEmoji;
use yii\db\Schema;

class MissingFormField extends MissingField implements FormFieldInterface
{
    use FormFieldTrait;

    public static function displayName(): string
    {
        return Craft::t('sprout-module-forms', 'Missing Field');
    }

    public function getExampleInputHtml(): string
    {
        return 'Missing Field';
    }

    public function getSettings(): array
    {
        return [];
    }
}
