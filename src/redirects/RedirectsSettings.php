<?php

namespace BarrelStrength\Sprout\redirects;

use BarrelStrength\Sprout\core\modules\SettingsRecord;
use BarrelStrength\Sprout\redirects\components\elements\RedirectElement;
use BarrelStrength\Sprout\redirects\redirects\MatchDefinition;
use BarrelStrength\Sprout\redirects\redirects\QueryStringStrategy;
use Craft;
use craft\config\BaseConfig;
use craft\models\FieldLayout;
use craft\records\Structure;

/**
 * @property int $structureUid
 * @property string|null $excludedUrlPatterns
 */
class RedirectsSettings extends BaseConfig
{
    // Project Config Settings

    public bool $enable404RedirectLog = false;

    public int $total404Redirects = 250;

    public bool $trackRemoteIp = false;

    public string $matchDefinition = MatchDefinition::URL_WITHOUT_QUERY_STRINGS;

    public string $queryStringStrategy = QueryStringStrategy::REMOVE_QUERY_STRINGS;

    public int $cleanupProbability = 1000;

    /**
     * Excluded URLs are stored as a string with individual values
     * separated by a new line character. Global settings are managed here
     * and site-specific settings are managed in the sprout_settings table.
     */
    public ?string $globallyExcludedUrlPatterns = null;

    private ?string $_siteExcludedUrlPatterns = null;

    // The Field Layout Config that will be saved to Project Config
    public array $fieldLayouts = [];

    // DB Settings

    /**
     * We have a single structure for all Sites as the structure is
     * only used behind the scenes and queries will always be limited
     * to a single Site.
     */
    public ?string $structureUid = null;

    public function enable404RedirectLog(bool $value): self
    {
        $this->enable404RedirectLog = $value;

        return $this;
    }

    public function total404Redirects(int $value): self
    {
        $this->total404Redirects = $value;

        return $this;
    }

    public function trackRemoteIp(bool $value): self
    {
        $this->trackRemoteIp = $value;

        return $this;
    }

    public function matchDefinition(string $value): self
    {
        $this->matchDefinition = $value;

        return $this;
    }

    public function queryStringStrategy(string $value): self
    {
        $this->queryStringStrategy = $value;

        return $this;
    }

    public function cleanupProbability(int $value): self
    {
        $this->cleanupProbability = $value;

        return $this;
    }

    public function getStructureId(): int
    {
        if (!$this->structureUid) {
            $this->structureUid = Craft::$app->getProjectConfig()->get(RedirectsModule::projectConfigPath('structureUid'));
        }

        $structureId = (int)Structure::find()
            ->select('id')
            ->where([
                'uid' => $this->structureUid,
            ])
            ->scalar();

        return $structureId;
    }

    public function setExcludedUrlPatterns(string $value = null): void
    {
        $this->_siteExcludedUrlPatterns = $value;
    }

    public function getSiteExcludedUrlPatterns(int $siteId): ?string
    {
        if ($this->_siteExcludedUrlPatterns) {
            return $this->_siteExcludedUrlPatterns;
        }

        $this->_siteExcludedUrlPatterns = SettingsRecord::find()
            ->select('settings')
            ->where([
                'siteId' => $siteId,
                'moduleId' => RedirectsModule::getModuleId(),
                'name' => 'siteExcludedUrlPatterns',
            ])
            ->scalar();

        return $this->_siteExcludedUrlPatterns;
    }

    public function getExcludedUrlPatterns(int $siteId): array
    {
        $siteExcludedUrlPatterns = $this->getSiteExcludedUrlPatterns($siteId);
        $excludedUrlPatterns = $this->globallyExcludedUrlPatterns . "\n" . $siteExcludedUrlPatterns;

        $excludedUrlPatterns = $excludedUrlPatterns
            ? array_filter(array_map('trim', explode("\n", $excludedUrlPatterns)))
            : [];

        // Remove empty lines and comments
        $excludedUrlPatterns = array_filter($excludedUrlPatterns, static fn($excludedUrlPattern): bool => !empty($excludedUrlPattern) && !str_starts_with($excludedUrlPattern, '#'));

        return $excludedUrlPatterns ?? [];
    }

    public function getFieldLayout(): FieldLayout
    {
        // If there is a field layout, it's saved with a UID key and we just need the first value
        if ($fieldLayout = reset($this->fieldLayouts)) {
            return FieldLayout::createFromConfig($fieldLayout);
        }

        return new FieldLayout([
            'type' => RedirectElement::class,
        ]);
    }
}

