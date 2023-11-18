<?php

namespace BarrelStrength\Sprout\meta\globals;

use BarrelStrength\Sprout\meta\db\SproutTable;
use BarrelStrength\Sprout\meta\MetaModule;
use Craft;
use craft\base\Component;
use craft\base\Field;
use craft\db\Query;
use craft\events\SiteEvent;
use craft\fields\Assets;
use craft\fields\PlainText;
use craft\helpers\Json;
use craft\models\Site;
use DateTime;
use DateTimeZone;

class GlobalMetadata extends Component
{
    public ?Globals $_globalMetadata = null;

    /**
     * Get Global Metadata values
     */
    public function getGlobalMetadata(Site $site = null): Globals
    {
        if ($this->_globalMetadata !== null) {
            return $this->_globalMetadata;
        }

        $siteId = $site->id ?? null;

        $query = (new Query())
            ->select('*')
            ->from([SproutTable::GLOBAL_METADATA]);

        if ($siteId) {
            $query->where(['siteId' => $siteId]);
        } else {
            $site = Craft::$app->getSites()->getPrimarySite();
            $query->where(['siteId' => $site->id]);
        }

        $results = $query->one();

        $results['identity'] = isset($results['identity']) ? Json::decode($results['identity']) : null;
        $results['contacts'] = isset($results['contacts']) ? Json::decode($results['contacts']) : null;
        $results['ownership'] = isset($results['ownership']) ? Json::decode($results['ownership']) : null;
        $results['social'] = isset($results['social']) ? Json::decode($results['social']) : null;
        $results['robots'] = isset($results['robots']) ? Json::decode($results['robots']) : null;
        $results['settings'] = isset($results['settings']) ? Json::decode($results['settings']) : null;

        $this->_globalMetadata = new Globals($results);

        return $this->_globalMetadata;
    }

    public function saveGlobalMetadata(string $globalColumn, Globals $globals): bool
    {
        $values = [];
        $values[$globalColumn] = $globals->getGlobalByKey($globalColumn, 'json');
        $values['siteId'] = $globals->siteId;

        $globalMetadataRecordExists = (new Query())
            ->select('*')
            ->from([SproutTable::GLOBAL_METADATA])
            ->where(['[[siteId]]' => $globals->siteId])
            ->exists();

        if (!$globalMetadataRecordExists) {
            $this->insertDefaultGlobalMetadata($globals->siteId);
        }

        Craft::$app->db->createCommand()->update(SproutTable::GLOBAL_METADATA,
            $values,
            ['siteId' => $globals->siteId]
        )->execute();

        return true;
    }

    public function getTransforms(): array
    {
        $options = [
            '' => Craft::t('sprout-module-meta', 'None'),
        ];

        $options[] = ['optgroup' => Craft::t('sprout-module-meta', 'Default Transforms')];

        $options['sprout-socialSquare'] = Craft::t('sprout-module-meta', 'Square – 400x400');
        $options['sprout-ogRectangle'] = Craft::t('sprout-module-meta', 'Rectangle – 1200x630 – Open Graph');
        $options['sprout-twitterRectangle'] = Craft::t('sprout-module-meta', 'Rectangle – 1024x512 – Twitter Card');

        $transforms = Craft::$app->getImageTransforms()->getAllTransforms();

        if (is_countable($transforms) ? count($transforms) : 0) {
            $options[] = ['optgroup' => Craft::t('sprout-module-meta', 'Custom Transforms')];

            foreach ($transforms as $transform) {
                $options[$transform->handle] = $transform->name;
            }
        }

        return $options;
    }

    public function getOrganizationOptions(): array
    {
        $jsonLdFile = Craft::getAlias('@BarrelStrength/Sprout/meta/schema/jsonld/tree.jsonld');
        $tree = file_get_contents($jsonLdFile);

        /** @var array $json */
        $json = json_decode($tree, true, 512, JSON_THROW_ON_ERROR);

        /** @var array $children */
        $children = $json['children'];

        foreach ($children as $value) {
            if ($value['name'] === 'Organization') {
                $json = $value['children'];
                break;
            }
        }

        $jsonByName = [];

        foreach ($json as $value) {
            $jsonByName[$value['name']] = $value;
        }

        return $jsonByName;
    }

    public function getDate($string): DateTime
    {
        return new DateTime($string['date'], new DateTimeZone(Craft::$app->getTimeZone()));
    }

    public function getJsonName($description): string
    {
        $name = preg_replace('#(?<!^)([A-Z])#', ' \\1', $description);

        if ($description === 'NGO') {
            $name = Craft::t('sprout-module-meta', 'Non Government Organization');
        }

        return $name;
    }

    /**
     * Returns all plain fields available given a type
     */
    public function getOptimizedOptions(string $type = PlainText::class, $handle = null, $settings = null): array
    {
        $options = [];
        $fields = Craft::$app->fields->getAllFields();

        $options[''] = Craft::t('sprout-module-meta', 'None');

        $options[] = ['optgroup' => Craft::t('sprout-module-meta', 'Use Existing Field (Recommended)')];

        if ($handle == 'optimizedTitleField') {
            $options['elementTitle'] = Craft::t('sprout-module-meta', 'Title');
        }

        /** @var Field $field */
        foreach ($fields as $field) {
            if ($field::class === $type) {
                $options[$field->id] = $field->name;
            }
        }

        $needPro = MetaModule::isPro() ? '' : '(Pro)';

        $options[] = [
            'optgroup' => Craft::t('sprout-module-meta', 'Define Custom Format {needPro}', [
                'needPro' => $needPro,
            ]),
        ];

        //        if (!isset($options[$settings[$handle]])) {
        //            $options[$settings[$handle]] = $settings[$handle];
        //        }

        $options[] = [
            'value' => 'custom',
            'label' => Craft::t('sprout-module-meta', 'Custom Format'),
            'disabled' => !MetaModule::isPro(),
        ];

        return $options;
    }

    /**
     * Returns keywords options
     */
    public function getKeywordsOptions(string $type = PlainText::class): array
    {
        $options = [];
        $fields = Craft::$app->fields->getAllFields();

        $options[''] = Craft::t('sprout-module-meta', 'None');
        $options[] = ['optgroup' => Craft::t('sprout-module-meta', 'Use Existing Field (Recommended)')];

        /** @var Field $field */
        foreach ($fields as $field) {
            if ($field::class == $type) {
                $options[$field->id] = $field->name;
            }
        }

        return $options;
    }

    /**
     * Returns all plain fields available given a type
     */
    public function getOptimizedTitleOptions($settings): array
    {
        return $this->getOptimizedOptions(PlainText::class, 'optimizedTitleField', $settings);
    }

    /**
     * Returns all plain fields available given a type
     */
    public function getOptimizedDescriptionOptions($settings): array
    {
        return $this->getOptimizedOptions(PlainText::class, 'optimizedDescriptionField', $settings);
    }

    /**
     * Returns all plain fields available given a type
     */
    public function getOptimizedAssetsOptions($settings): array
    {
        return $this->getOptimizedOptions(Assets::class, 'optimizedImageField', $settings);
    }

    public function hasActiveMetadata($type, $metadataModel): bool
    {
        switch ($type) {
            case 'search':

                if (($metadataModel['optimizedTitle'] || $metadataModel['title']) &&
                    ($metadataModel['optimizedDescription'] || $metadataModel['description'])
                ) {
                    return true;
                }

                break;

            case 'openGraph':

                if (($metadataModel['optimizedTitle'] || $metadataModel['title']) &&
                    ($metadataModel['optimizedDescription'] || $metadataModel['description']) &&
                    ($metadataModel['optimizedImage'] || $metadataModel['ogImage'])
                ) {
                    return true;
                }

                break;

            case 'twitterCard':

                if (($metadataModel['optimizedTitle'] || $metadataModel['title']) &&
                    ($metadataModel['optimizedDescription'] || $metadataModel['description']) &&
                    ($metadataModel['optimizedImage'] || $metadataModel['twitterImage'])
                ) {
                    return true;
                }

                break;
        }

        return false;
    }

    public function handleDefaultSiteMetadata(SiteEvent $event): void
    {
        if (!$event->isNew) {
            return;
        }

        if (!MetaModule::isEnabled()) {
            return;
        }

        $this->insertDefaultGlobalMetadata($event->site->id);
    }

    public function insertDefaultGlobalMetadata(int $siteId): void
    {
        $defaultSettings = '{
            "metaDivider":"-",
            "defaultOgType":"website",
            "ogTransform":"sprout-socialSquare",
            "twitterTransform":"sprout-socialSquare",
            "defaultTwitterCard":"summary",
            "appendTitleValueOnHomepage":"",
            "appendTitleValue": ""}
        ';

        Craft::$app->getDb()->createCommand()->insert(SproutTable::GLOBAL_METADATA, [
            'siteId' => $siteId,
            'identity' => null,
            'ownership' => null,
            'contacts' => null,
            'social' => null,
            'robots' => null,
            'settings' => $defaultSettings,
        ])->execute();
    }
}
