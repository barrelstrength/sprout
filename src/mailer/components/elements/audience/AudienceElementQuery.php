<?php

namespace BarrelStrength\Sprout\mailer\components\elements\audience;

use BarrelStrength\Sprout\mailer\components\audiences\SubscriberListAudienceType;
use BarrelStrength\Sprout\mailer\MailerModule;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;

class AudienceElementQuery extends ElementQuery
{
    public ?int $elementId = null;

    public string $handle = '';

    public mixed $type = null;

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

    public function type(string $value): AudienceElementQuery
    {
        $this->type = $value;

        return $this;
    }

    protected function beforePrepare(): bool
    {
        $this->joinElementTable('sprout_audiences');

        $this->query->select([
            'sprout_audiences.elementId',
            'sprout_audiences.type',
            'sprout_audiences.settings',
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

        if ($this->type) {
            $this->subQuery->andWhere(Db::parseParam(
                'sprout_audiences.type', $this->type
            ));
        }

        $settings = MailerModule::getInstance()->getSettings();

        if (!$settings->enableSubscriberLists) {
            $this->subQuery->andWhere(Db::parseParam(
                'sprout_audiences.type', SubscriberListAudienceType::class, 'not'
            ));
        }

        return parent::beforePrepare();
    }
}
