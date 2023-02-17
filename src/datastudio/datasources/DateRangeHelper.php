<?php

namespace BarrelStrength\Sprout\datastudio\datasources;

use Craft;
use craft\helpers\DateTimeHelper;
use DateTimeInterface;
use DateTimeZone;

class DateRangeHelper
{
    public const RANGE_THIS_WEEK = 'thisWeek';
    public const RANGE_THIS_MONTH = 'thisMonth';
    public const RANGE_LAST_MONTH = 'lastMonth';
    public const RANGE_THIS_QUARTER = 'thisQuarter';
    public const RANGE_LAST_QUARTER = 'lastQuarter';
    public const RANGE_THIS_YEAR = 'thisYear';
    public const RANGE_LAST_YEAR = 'lastYear';
    public const RANGE_CUSTOM = 'customRange';

    /**
     * Convert DateTime to UTC to get correct result when querying SQL. SQL data is always on UTC.
     */
    public static function getUtcDateTime(mixed $dateSetting): ?DateTimeInterface
    {
        $dateTime = DateTimeHelper::toDateTime($dateSetting, true);

        if (!$dateTime) {
            return null;
        }

        $timeZone = new DateTimeZone('UTC');

        return $dateTime->setTimezone($timeZone);
    }

    public static function getStartEndDateRange($value): array
    {
        // The date function still return date based on the cpPanel timezone settings
        $dateTime = [
            'startDate' => date('Y-m-d H:i:s'),
            'endDate' => date('Y-m-d H:i:s'),
        ];

        switch ($value) {

            case self::RANGE_THIS_WEEK:
                $dateTime['startDate'] = date('Y-m-d H:i:s', strtotime('-7 days'));
                break;

            case self::RANGE_THIS_MONTH:
                $dateTime['startDate'] = date('Y-m-1 00:00:00');
                $dateTime['endDate'] = date('Y-m-t 00:00:00');
                break;

            case self::RANGE_LAST_MONTH:
                $dateTime['startDate'] = date('Y-m-1 00:00:00', strtotime('-1 month'));
                $dateTime['endDate'] = date('Y-m-t 00:00:00', strtotime('-1 month'));
                break;

            case self::RANGE_THIS_QUARTER:
                $dateTime = self::thisQuarter();
                break;

            case self::RANGE_LAST_QUARTER:
                $dateTime = self::lastQuarter();
                break;

            case self::RANGE_THIS_YEAR:
                $dateTime['startDate'] = date('Y-1-1 00:00:00');
                $dateTime['endDate'] = date('Y-12-t 00:00:00');
                break;

            case self::RANGE_LAST_YEAR:
                $dateTime['startDate'] = date('Y-1-1 00:00:00', strtotime('-1 year'));
                $dateTime['endDate'] = date('Y-12-t 00:00:00', strtotime('-1 year'));
                break;
        }

        return $dateTime;
    }

    public static function getDateRanges($withQuarter = true): array
    {
        $currentMonth = date('F');
        $lastMonth = date('F', strtotime(date('Y-m') . ' -1 month'));
        $thisQuarter = self::thisQuarter();
        $thisQuarterInitialMonth = date('F', strtotime($thisQuarter['startDate']));
        $thisQuarterFinalMonth = date('F', strtotime($thisQuarter['endDate']));
        $thisQuarterYear = date('Y', strtotime($thisQuarter['endDate']));

        $lastQuarter = self::lastQuarter();
        $lastQuarterInitialMonth = date('F', strtotime($lastQuarter['startDate']));
        $lastQuarterFinalMonth = date('F', strtotime($lastQuarter['endDate']));
        $lastQuarterYear = date('Y', strtotime($lastQuarter['endDate']));

        $currentYear = date('Y');
        $previousYear = date('Y', strtotime('-1 year'));

        $ranges = [
            self::RANGE_THIS_WEEK => Craft::t('sprout-module-data-studio', 'Last 7 Days'),
            self::RANGE_THIS_MONTH => Craft::t('sprout-module-data-studio', 'This Month ({month})', ['month' => $currentMonth]),
            self::RANGE_LAST_MONTH => Craft::t('sprout-module-data-studio', 'Last Month ({month})', ['month' => $lastMonth]),
        ];

        if ($withQuarter) {
            $ranges = array_merge($ranges, [
                self::RANGE_THIS_QUARTER => Craft::t('sprout-module-data-studio', 'This Quarter ({iMonth} - {fMonth} {year})', [
                    'iMonth' => $thisQuarterInitialMonth,
                    'fMonth' => $thisQuarterFinalMonth,
                    'year' => $thisQuarterYear,
                ]),
                self::RANGE_LAST_QUARTER => Craft::t('sprout-module-data-studio', 'Last Quarter ({iMonth} - {fMonth} {year})', [
                    'iMonth' => $lastQuarterInitialMonth,
                    'fMonth' => $lastQuarterFinalMonth,
                    'year' => $lastQuarterYear,
                ]),
            ]);
        }

        return array_merge($ranges, [
            self::RANGE_THIS_YEAR => Craft::t('sprout-module-data-studio', 'This Year ({year})', ['year' => $currentYear]),
            self::RANGE_LAST_YEAR => Craft::t('sprout-module-data-studio', 'Last Year ({year})', ['year' => $previousYear]),
            self::RANGE_CUSTOM => Craft::t('sprout-module-data-studio', 'Custom Date Range'),
        ]);
    }

    public static function thisQuarter(): array
    {
        $startDate = '';
        $endDate = '';
        $current_month = date('m');
        $current_year = date('Y');
        if ($current_month >= 1 && $current_month <= 3) {
            // timestamp or 1-January 12:00:00 AM
            $startDate = strtotime('1-January-' . $current_year);
            // timestamp or 1-April 12:00:00 AM means end of 31 March
            $endDate = strtotime('31-March-' . $current_year);
        } elseif ($current_month >= 4 && $current_month <= 6) {
            // timestamp or 1-April 12:00:00 AM
            $startDate = strtotime('1-April-' . $current_year);
            // timestamp or 1-July 12:00:00 AM means end of 30 June
            $endDate = strtotime('30-June-' . $current_year);
        } elseif ($current_month >= 7 && $current_month <= 9) {
            // timestamp or 1-July 12:00:00 AM
            $startDate = strtotime('1-July-' . $current_year);
            // timestamp or 1-October 12:00:00 AM means end of 30 September
            $endDate = strtotime('30-September-' . $current_year);
        } elseif ($current_month >= 10 && $current_month <= 12) {
            // timestamp or 1-October 12:00:00 AM
            $startDate = strtotime('1-October-' . $current_year);
            // timestamp or 1-January Next year 12:00:00 AM means end of 31 December this year
            $endDate = strtotime('31-December-' . $current_year);
        }

        return [
            'startDate' => date('Y-m-d H:i:s', $startDate),
            'endDate' => date('Y-m-d H:i:s', $endDate),
        ];
    }

    public static function lastQuarter(): array
    {
        $startDate = '';
        $endDate = '';
        $current_month = date('m');
        $current_year = date('Y');

        if ($current_month >= 1 && $current_month <= 3) {
            // timestamp or 1-October Last Year 12:00:00 AM
            $startDate = strtotime('1-October-' . ($current_year - 1));
            // timestamp or 1-January  12:00:00 AM means end of 31 December Last year
            $endDate = strtotime('31-December-' . ($current_year - 1));
        } elseif ($current_month >= 4 && $current_month <= 6) {
            // timestamp or 1-January 12:00:00 AM
            $startDate = strtotime('1-January-' . $current_year);
            $endDate = strtotime('31-March-' . $current_year);
            // timestamp or 1-April 12:00:00 AM means end of 31 March
        } elseif ($current_month >= 7 && $current_month <= 9) {
            // timestamp or 1-April 12:00:00 AM
            $startDate = strtotime('1-April-' . $current_year);
            // timestamp or 1-July 12:00:00 AM means end of 30 June
            $endDate = strtotime('30-June-' . $current_year);
        } elseif ($current_month >= 10 && $current_month <= 12) {
            // timestamp or 1-July 12:00:00 AM
            $startDate = strtotime('1-July-' . $current_year);
            // timestamp or 1-October 12:00:00 AM means end of 30 September
            $endDate = strtotime('30-September-' . $current_year);
        }

        return [
            'startDate' => date('Y-m-d H:i:s', $startDate),
            'endDate' => date('Y-m-d H:i:s', $endDate),
        ];
    }
}
