<?php

namespace BarrelStrength\Sprout\forms\components\formfields;

use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\formfields\FormFieldInterface;
use BarrelStrength\Sprout\forms\formfields\FormFieldTrait;
use BarrelStrength\Sprout\forms\FormsModule;
use Craft;
use craft\fields\Users as CraftUsers;

class UsersFormField extends CraftUsers implements FormFieldInterface
{
    use FormFieldTrait;

    public string $cssClasses = '';

    public string $usernameFormat = 'fullName';

    protected string $settingsTemplate = 'sprout-module-forms/_components/fields/Users/settings';

    public function getSvgIconPath(): string
    {
        return '@Sprout/Assets/dist/static/fields/icons/users.svg';
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

        return [
            'name' => $this->handle,
            'value' => $value->ids(),
            'field' => $this,
            'submission' => $submission,
            'renderingOptions' => $renderingOptions,
            'users' => $users,
        ];
    }

    //public function getFrontEndInputHtml($value, SubmissionElement $submission, array $renderingOptions = null): Markup
    //{
    //    $users = FormsModule::getInstance()->frontEndFields->getFrontEndUsers($this->getSettings());
    //
    //    $rendered = Craft::$app->getView()->renderTemplate('users/input', [
    //            'name' => $this->handle,
    //            'value' => $value->ids(),
    //            'field' => $this,
    //            'submission' => $submission,
    //            'renderingOptions' => $renderingOptions,
    //            'users' => $users,
    //        ]
    //    );
    //
    //    return TemplateHelper::raw($rendered);
    //}

    public function getCompatibleCraftFieldTypes(): array
    {
        return [
            CraftUsers::class,
        ];
    }
}
