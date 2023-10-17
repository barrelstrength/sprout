<?php

namespace BarrelStrength\Sprout\forms\components\widgets;

use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\FormsModule;
use Craft;
use craft\base\Widget;

class RecentSubmissionsWidget extends Widget
{
    public int $formId = 0;

    public int $limit = 10;

    public string $showDate;

    public static function displayName(): string
    {
        return Craft::t('sprout-module-forms', 'Recent Submissions (Sprout)');
    }

    public static function icon(): null|string
    {
        return Craft::getAlias('@Sprout/Assets/dist/static/forms/icons/icon-mask.svg');
    }

    public function getTitle(): ?string
    {
        // Concat form name if the user select a specific form
        if ($this->formId !== 0) {
            $form = FormsModule::getInstance()->forms->getFormById($this->formId);

            if ($form !== null) {
                return Craft::t('sprout-module-forms', 'Recent {formName} Submissions', [
                    'formName' => $form->name,
                ]);
            }
        }

        return static::displayName();
    }

    public function getBodyHtml(): ?string
    {
        $query = SubmissionElement::find();

        if ($this->formId !== 0) {
            $query->formId = $this->formId;
        }

        $query->limit = $this->limit;

        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/widgets/RecentSubmissions/body', [
            'submissions' => $query->all(),
            'widget' => $this,
        ]);
    }

    public function getSettingsHtml(): ?string
    {
        $forms = [
            0 => Craft::t('sprout-module-forms', 'All forms'),
        ];

        $sproutForms = FormsModule::getInstance()->forms->getAllForms();

        if ($sproutForms) {
            foreach ($sproutForms as $form) {
                $forms[$form->id] = $form->name;
            }
        }

        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/widgets/RecentSubmissions/settings', [
            'sproutForms' => $forms,
            'widget' => $this,
        ]);
    }
}
