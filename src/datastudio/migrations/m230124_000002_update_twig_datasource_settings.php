<?php

namespace BarrelStrength\Sprout\datastudio\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;
use craft\web\View;

class m230124_000002_update_twig_datasource_settings extends Migration
{
    public const DATASETS_TABLE = '{{%sprout_datasets}}';
    public const TWIG_DATA_SOURCE_CLASS = 'BarrelStrength\Sprout\datastudio\components\datasources\CustomTwigTemplateQueryDataSource';

    public function safeUp(): void
    {
        $twigDataSets = (new Query())
            ->select([
                'id',
                'settings',
            ])
            ->from([self::DATASETS_TABLE])
            ->where([
                'type' => self::TWIG_DATA_SOURCE_CLASS,
            ])
            ->all();

        foreach ($twigDataSets as $twigDataSet) {

            if (!$newSettings = Json::decode($twigDataSet['settings'])) {
                continue;
            }

            $resultsTemplate = $newSettings['resultsTemplate'] ?? null;
            $settingsTemplate = $newSettings['settingsTemplate'] ?? $newSettings['optionsTemplate'] ?? null;
            unset($newSettings['optionsTemplate']);

            $newSettings['resultsTemplate'] = $this->ensureSettingIncludesFileExtension($resultsTemplate);
            $newSettings['settingsTemplate'] = $this->ensureSettingIncludesFileExtension($settingsTemplate);

            $this->update(self::DATASETS_TABLE, [
                'settings' => Json::encode($newSettings),
            ], ['id' => $twigDataSet['id']], [], true);
        }
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }

    public function ensureSettingIncludesFileExtension(string $template): ?string
    {
        $view = Craft::$app->getView();

        if (!$view->doesTemplateExist($template, View::TEMPLATE_MODE_SITE)) {
            return null;
        }

        // Template exists, now make sure we have the full filename in the setting
        $extensions = Craft::$app->getConfig()->getGeneral()->defaultTemplateExtensions;

        // First try to return our template if the extension is present
        foreach ($extensions as $extension) {
            if (str_ends_with($template, $extension)) {
                return $template;
            }
        }

        // Second, add the extension to the filename
        foreach ($extensions as $extension) {
            if ($view->doesTemplateExist($template . '.' . $extension, View::TEMPLATE_MODE_SITE)) {
                return $template . '.' . $extension;
            }
        }

        return null;
    }
}
