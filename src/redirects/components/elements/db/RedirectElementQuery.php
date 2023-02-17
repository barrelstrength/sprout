<?php

namespace BarrelStrength\Sprout\redirects\components\elements\db;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;

class RedirectElementQuery extends ElementQuery
{
    public ?string $oldUrl = null;

    public ?string $newUrl = null;

    public ?array $matchStrategy = [];

    /** Query one or more statusCodes as an array [301,302] */
    public array $statusCode = [];

    public ?int $redirectLimit = null;

    public function init(): void
    {
        if ($this->withStructure === null) {
            $this->withStructure = true;
        }

        parent::init();
    }

    public function matchStrategy(array $value): self
    {
        $this->matchStrategy = $value;

        return $this;
    }

    public function statusCode(array $value): self
    {
        $this->statusCode = $value;

        return $this;
    }

    protected function beforePrepare(): bool
    {
        $this->joinElementTable('sprout_redirects');

        $this->query->select([
            'sprout_redirects.id',
            'sprout_redirects.oldUrl',
            'sprout_redirects.newUrl',
            'sprout_redirects.statusCode',
            'sprout_redirects.matchStrategy',
            'sprout_redirects.count',
            'sprout_redirects.lastRemoteIpAddress',
            'sprout_redirects.lastReferrer',
            'sprout_redirects.lastUserAgent',
            'sprout_redirects.dateLastUsed',
        ]);

        if ($this->id) {
            $this->subQuery->andWhere(Db::parseParam(
                'sprout_redirects.id', $this->id)
            );
        }

        if ($this->oldUrl) {
            $this->subQuery->andWhere(Db::parseParam(
                'sprout_redirects.oldUrl', $this->oldUrl)
            );
        }

        if ($this->newUrl) {
            $this->subQuery->andWhere(Db::parseParam(
                'sprout_redirects.newUrl', $this->newUrl)
            );
        }

        if ($this->matchStrategy) {
            $this->subQuery->andWhere(Db::parseParam(
                'sprout_redirects.matchStrategy', $this->matchStrategy)
            );
        }

        if ($this->statusCode) {
            $this->subQuery->andWhere(Db::parseParam(
                'sprout_redirects.statusCode', $this->statusCode)
            );
        }

        if ($this->redirectLimit) {
            $this->limit($this->redirectLimit);
        }

        return parent::beforePrepare();
    }
}
