<?php

namespace BarrelStrength\Sprout\mailer\components\elements\audience;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;

class AudienceElementQuery extends ElementQuery
{
    public ?int $elementId = null;

    public string $handle = '';

    public mixed $groupId = null;

    public mixed $audienceType = null;

    public function __set($name, $value)
    {
        switch ($name) {
            case 'elementId':
                $this->elementId($value);
                break;
            case 'handle':
                $this->handle($value);
                break;
            default:
                parent::__set($name, $value);
        }
    }

    /**
     * @return static self reference
     */
    public function elementId(?int $value): AudienceElementQuery
    {
        $this->elementId = $value;

        return $this;
    }

    /**
     * @return static self reference
     */
    public function handle(string $value): AudienceElementQuery
    {
        $this->handle = $value;

        return $this;
    }

    public function groupId($value): AudienceElementQuery
    {
        $this->groupId = $value;

        return $this;
    }

    public function audienceType(string $value): AudienceElementQuery
    {
        $this->audienceType = $value;

        return $this;
    }

    protected function beforePrepare(): bool
    {
        $this->joinElementTable('sprout_audiences');

        $this->query->select([
            'sprout_audiences.elementId',
            'sprout_audiences.groupId',
            'sprout_audiences.audienceType',
            'sprout_audiences.audienceSettings',
            'sprout_audiences.name',
            'sprout_audiences.handle',
            'sprout_audiences.count',
        ]);

        if ($this->elementId) {
            $this->subQuery->andWhere(Db::parseParam(
                'sprout_audiences.elementId', $this->elementId
            ));
        }

        if ($this->handle) {
            $this->subQuery->andWhere(Db::parseParam(
                'sprout_audiences.handle', $this->handle
            ));
        }

        if ($this->groupId) {
            $this->subQuery->andWhere(Db::parseParam(
                'sprout_audiences.groupId', $this->groupId
            ));
        }

        if ($this->audienceType) {
            $this->subQuery->andWhere(Db::parseParam(
                'sprout_audiences.audienceType', $this->audienceType
            ));
        }

        return parent::beforePrepare();
    }
}
