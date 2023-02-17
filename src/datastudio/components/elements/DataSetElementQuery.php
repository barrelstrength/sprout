<?php

namespace BarrelStrength\Sprout\datastudio\components\elements;

use BarrelStrength\Sprout\datastudio\DataStudioModule;
use Craft;
use craft\db\QueryAbortedException;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;

class DataSetElementQuery extends ElementQuery
{
    /**
     * Whether to only return data sets that the user has permission to view.
     */
    public ?bool $viewable = null;

    public ?int $groupId = null;

    public ?string $type = null;

    public function viewable(?bool $value = true): self
    {
        $this->viewable = $value;

        return $this;
    }

    protected function beforePrepare(): bool
    {
        $this->joinElementTable('sprout_datasets');

        $this->query->select([
            'sprout_datasets.name',
            'sprout_datasets.nameFormat',
            'sprout_datasets.handle',
            'sprout_datasets.description',
            'sprout_datasets.type',
            'sprout_datasets.allowHtml',
            'sprout_datasets.sortOrder',
            'sprout_datasets.sortColumn',
            'sprout_datasets.delimiter',
            'sprout_datasets.visualizationType',
            'sprout_datasets.visualizationSettings',
            'sprout_datasets.settings',
            'sprout_datasets.groupId',
            'sprout_datasets.enabled',
        ]);

        if ($this->groupId) {
            $this->query->andWhere(Db::parseParam(
                '[[sprout_datasets.groupId]]', $this->groupId)
            );
        }

        if ($this->type) {
            $this->query->andWhere(Db::parseParam(
                '[[sprout_datasets.type]]', $this->type)
            );
        }

        if ($this->viewable) {
            $this->applyAuthParam();
        }

        return parent::beforePrepare();
    }

    private function applyAuthParam(): void
    {
        $user = Craft::$app->getUser()->getIdentity();

        if (!$user) {
            throw new QueryAbortedException();
        }

        $dataSourceTypes = DataStudioModule::getInstance()->dataSources->getDataSourceTypes();
        $authorizedDataSourceTypes = [];

        foreach ($dataSourceTypes as $dataSourceType) {
            if ($user->can(DataStudioModule::p('viewReports:' . $dataSourceType))) {
                $authorizedDataSourceTypes[] = $dataSourceType;
            }
        }

        $this->query->andWhere([
            'in', '[[sprout_datasets.type]]', $authorizedDataSourceTypes,
        ]);
    }
}
