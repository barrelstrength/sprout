<?php

namespace BarrelStrength\Sprout\forms\components\formfields;

use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\formfields\FormFieldInterface;
use BarrelStrength\Sprout\forms\formfields\FormFieldTrait;
use BarrelStrength\Sprout\forms\formfields\GroupLabel;
use BarrelStrength\Sprout\forms\FormsModule;
use Craft;
use craft\fields\Users as CraftUsers;

class UsersFormField extends CraftUsers implements FormFieldInterface
{
    use FormFieldTrait;

    public string $usernameFormat = 'fullName';

    protected string $settingsTemplate = 'sprout-module-forms/_components/fields/Users/settings';

    public static function getGroupLabel(): string
    {
        return GroupLabel::label(GroupLabel::GROUP_RELATIONS);
    }

    public function selectorIcon(): string
    {
        return 'users';
    }

    public function getFieldInputFolder(): string
    {
        return 'users';
    }

    public function getExampleInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/Users/example',
            [
                'field' => $this,
            ]
        );
    }

    public function getFrontEndInputVariables($value, SubmissionElement $submission, array $renderingOptions = null): array
    {
        $users = FormsModule::getInstance()->frontEndFields->getFrontEndUsers($this->getSettings());
        $multiple = $this->maxRelations === null || $this->maxRelations > 1;

        return [
            'name' => $this->handle,
            'value' => $value->ids(),
            'renderingOptions' => $renderingOptions,
            'users' => $users,
            'multiple' => $multiple,
            'selectionLabel' => $this->selectionLabel,
            'usernameFormat' => $this->usernameFormat,
        ];
    }

    public function getCompatibleCraftFieldTypes(): array
    {
        return [
            CraftUsers::class,
        ];
    }
}
