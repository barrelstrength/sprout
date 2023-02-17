<?php

namespace BarrelStrength\Sprout\mailer\emailthemes;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\components\emailthemes\DefaultEmailTheme;
use Craft;
use craft\base\Component;
use craft\events\ConfigEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\models\FieldLayout;
use Throwable;

class EmailThemes extends Component
{
    public const EVENT_REGISTER_EMAIL_THEMES = 'registerSproutEmailThemes';

    public function getEmailThemeTypes(): array
    {
        $emailThemes[] = DefaultEmailTheme::class;

        $event = new RegisterComponentTypesEvent([
            'types' => $emailThemes,
        ]);

        $this->trigger(self::EVENT_REGISTER_EMAIL_THEMES, $event);

        return $event->types;
    }

    /**
     * @return string[]
     */
    public function getEmailThemes(): array
    {
        //        $emailThemeTypes = $this->getEmailThemeTypes();
        $themes = [];

        //        foreach ($emailThemeTypes as $emailThemeType) {
        //            $theme = new $emailThemeType();
        //            $themes[$theme->handle()] = $theme;
        //        }

        $configThemes = EmailThemeRecord::find()->all();

        if ($configThemes) {
            foreach ($configThemes as $configThemeRecord) {

                $type = $configThemeRecord->type;
                $customTheme = new $type();
                $customTheme->id = $configThemeRecord->id;
                $customTheme->fieldLayoutId = $configThemeRecord->fieldLayoutId;
                $customTheme->name = $configThemeRecord->name;
                $customTheme->htmlEmailTemplatePath = $configThemeRecord->htmlEmailTemplatePath;
                $customTheme->copyPasteEmailTemplatePath = $configThemeRecord->copyPasteEmailTemplatePath;
                //                $customTheme->settings = $configThemeRecord->settings;

                $themes[$customTheme->id] = $customTheme;
            }
        }

        uasort($themes, static function($a, $b): int {
            /**
             * @var EmailTheme $a
             * @var EmailTheme $b
             */
            return $a->name <=> $b->name;
        });

        return $themes;
    }

    public function getEmailThemeByHandle(string $handle = null): ?EmailTheme
    {
        $emailThemes = $this->getEmailThemes();

        return $emailThemes[$handle] ?? null;
    }

    public function getEmailThemeById($id): ?EmailTheme
    {
        if (!$emailThemeRecord = EmailThemeRecord::findOne($id)) {
            return null;
        }

        $emailTheme = new $emailThemeRecord->type();
        $emailTheme->id = $emailThemeRecord->id;
        $emailTheme->fieldLayoutId = $emailThemeRecord->fieldLayoutId;
        $emailTheme->name = $emailThemeRecord->name;
        $emailTheme->htmlEmailTemplatePath = $emailThemeRecord->htmlEmailTemplatePath;
        $emailTheme->copyPasteEmailTemplatePath = $emailThemeRecord->copyPasteEmailTemplatePath;

        return $emailTheme;
    }

    public function getDefaultEmailTheme()
    {
        return EmailThemeRecord::find()
            ->select('id')
            ->orderBy('sortOrder ASC')
            ->scalar();
    }

    /**
     * Copied from craft/commerce/services/Orders::handleChangedFieldLayout()
     */
    public function handleChangedFieldLayout(ConfigEvent $event): void
    {
        $emailThemeUid = $event->tokenMatches[0];
        $data = $event->newValue;

        ProjectConfigHelper::ensureAllSitesProcessed();
        ProjectConfigHelper::ensureAllFieldsProcessed();

        $emailThemeRecord = EmailThemeRecord::find()
            ->where(['uid' => $emailThemeUid])
            ->one();

        if (!$emailThemeRecord) {
            $emailThemeRecord = new EmailThemeRecord();
        }

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {

            if (!empty($data['fieldLayouts'])) {
                // Save the field layout
                $layout = FieldLayout::createFromConfig(reset($data['fieldLayouts']));
                $layout->id = $emailThemeRecord->fieldLayoutId;
                $layout->type = EmailElement::class;
                $layout->uid = key($data['fieldLayouts']);
                $layout->reservedFieldHandles = [
                    'fieldLayoutId',
                    'name',
                    'type',
                    'htmlEmailTemplatePath',
                    'copyPasteEmailTemplatePath',
                ];
                Craft::$app->getFields()->saveLayout($layout, false);
                $emailThemeRecord->fieldLayoutId = $layout->id;
            } elseif ($emailThemeRecord->fieldLayoutId) {
                // Delete the field layout
                Craft::$app->getFields()->deleteLayoutById($emailThemeRecord->fieldLayoutId);
                $emailThemeRecord->fieldLayoutId = null;
            }

            $emailThemeRecord->name = $data['name'];
            $emailThemeRecord->htmlEmailTemplatePath = $data['htmlEmailTemplatePath'];
            $emailThemeRecord->copyPasteEmailTemplatePath = $data['copyPasteEmailTemplatePath'];
            $emailThemeRecord->uid = $emailThemeUid;

            $emailThemeRecord->save();

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    public function handleDeletedFieldLayout(ConfigEvent $event): void
    {
        //        \Craft::dd($event);
        //        Craft::$app->getFields()->deleteLayoutsByType(RedirectElement::class);
    }
}
