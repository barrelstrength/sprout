<?php

namespace BarrelStrength\Sprout\datastudio\datasources;

use craft\validators\DateTimeValidator;
use DateTimeInterface;

trait DateRangeTrait
{
    public string $dateRange = DateRangeHelper::RANGE_CUSTOM;

    public ?DateTimeInterface $startDate = null;

    public ?DateTimeInterface $endDate = null;

    public function getStartDate(): ?DateTimeInterface
    {
        if (!$this->startDate) {
            $this->initDateRange();
        }

        return $this->startDate;
    }

    public function getEndDate(): ?DateTimeInterface
    {
        if (!$this->endDate) {
            $this->initDateRange();
        }

        return $this->endDate;
    }

    public function initDateRange(): void
    {
        if ($this->dateRange === DateRangeHelper::RANGE_CUSTOM) {
            $startDateSetting = $this->startDate;
            $endDateSetting = $this->endDate;
        } else {
            $startEndDate = DateRangeHelper::getStartEndDateRange($this->dateRange);

            $startDateSetting = $startEndDate['startDate'];
            $endDateSetting = $startEndDate['endDate'];
        }

        $this->startDate = DateRangeHelper::getUtcDateTime($startDateSetting);
        $this->endDate = DateRangeHelper::getUtcDateTime($endDateSetting);
    }

    public function defineDateRangeRules(): array
    {
        if ($this->dateRange === DateRangeHelper::RANGE_CUSTOM) {
            $this->setScenario(self::SCENARIO_CUSTOM_RANGE);
        }

        $rules[] = ['dateRange', 'required'];
        $rules[] = [['startDate', 'endDate'], 'required', 'on' => self::SCENARIO_CUSTOM_RANGE];
        $rules[] = [['startDate', 'endDate'], DateTimeValidator::class];

        return $rules;
    }
}
