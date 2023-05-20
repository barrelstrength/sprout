<?php

namespace BarrelStrength\Sprout\datastudio\components\datasources;

use BarrelStrength\Sprout\datastudio\components\elements\DataSetElement;
use BarrelStrength\Sprout\datastudio\datasources\DataSource;
use BarrelStrength\Sprout\datastudio\datasources\DateRangeHelper;
use BarrelStrength\Sprout\datastudio\datasources\DateRangeInterface;
use BarrelStrength\Sprout\datastudio\datasources\DateRangeTrait;
use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\DateTimeHelper;
use DateTime;

class CommerceOrderHistoryDataSource extends DataSource implements DateRangeInterface
{
    use DateRangeTrait;

    public bool $calculateTotals = true;

    public function datetimeAttributes(): array
    {
        return [
            'startDate',
            'endDate',
        ];
    }

    public static function getHandle(): string
    {
        return 'commerce-order-history';
    }

    public static function displayName(): string
    {
        return Craft::t('sprout-module-data-studio', 'Commerce Order History');
    }

    public function getDescription(): string
    {
        return Craft::t('sprout-module-data-studio', 'Displays all orders by date range');
    }

    public function getResults(DataSetElement $dataSet): array
    {
        if ($this->calculateTotals) {
            return $this->getReportWithCalculateTotals();
        }

        return $this->getReportWithLineItems();
    }

    public function getSettingsHtml(): ?string
    {
        $dateRanges = DateRangeHelper::getDateRanges();

        return Craft::$app->getView()->renderTemplate('sprout-module-data-studio/_components/datasources/CommerceOrderHistory/settings.twig', [
            'defaultStartDate' => new DateTime(),
            'defaultEndDate' => new DateTime(),
            'dateRanges' => $dateRanges,
            'settings' => $this->getSettings(),
        ]);
    }

    /**
     * Aggregates all results into a single line with totals
     */
    protected function getReportWithCalculateTotals(): array
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $query = new Query();
        $query->select('SUM([[orders.totalPaid]]) as totalRevenue')
            ->from('{{%commerce_orders}} as orders')
            ->leftJoin(['elements' => Table::ELEMENTS], '[[orders.id]] = [[elements.id]]')
            ->where(['elements.dateDeleted' => null]);

        if ($startDate && $endDate) {
            $query->andWhere(['>=', '[[orders.dateOrdered]]', $startDate->format('Y-m-d H:i:s')]);
            $query->andWhere(['<=', '[[orders.dateOrdered]]', $endDate->format('Y-m-d H:i:s')]);
        }

        $results = $query->all();

        if (!empty($results)) {
            foreach ($results as $key => $result) {
                $totalTax = (int)$this->getTotalAdjustmentByType('Tax');
                $totalShipping = (int)$this->getTotalAdjustmentByType('Shipping');

                $productRevenue = $result['totalRevenue'] - ($totalTax + $totalShipping);

                $results[$key]['Product Revenue'] = number_format($productRevenue, 2);
                $results[$key]['Tax'] = number_format($totalTax, 2);
                $results[$key]['Shipping'] = number_format($totalShipping, 2);
                $results[$key]['Total Revenue'] = number_format($result['totalRevenue'], 2);

                unset($results[$key]['totalRevenue']);
            }
        }

        return $results;
    }

    /**
     * Returns a row for each order in a given time period
     */
    protected function getReportWithLineItems(): array
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $query = new Query();

        $query->select('[[orders.id]] as orderId, 
                      [[orders.number]],
                      [[orders.totalPaid]],
                      [[orders.dateOrdered]]')
            ->from('{{%commerce_orders}} as orders')
            ->leftJoin(['elements' => Table::ELEMENTS], '[[orders.id]] = [[elements.id]]')
            ->where(['elements.dateDeleted' => null]);

        if ($startDate && $endDate) {
            $query->andWhere(['>=', '[[orders.dateOrdered]]', $startDate->format('Y-m-d H:i:s')]);
            $query->andWhere(['<=', '[[orders.dateOrdered]]', $endDate->format('Y-m-d H:i:s')]);
        }

        $query->orderBy(['[[orders.dateOrdered]]' => SORT_DESC]);

        $orders = $query->all();

        if (!empty($orders)) {
            foreach ($orders as $key => $order) {
                $totalTax = (int)$this->getTotalAdjustmentByType('Tax', $order);
                $totalShipping = (int)$this->getTotalAdjustmentByType('Shipping', $order);

                $productRevenue = $orders[$key]['totalPaid'] - ($totalShipping + $totalTax);

                $dateOrdered = DateTimeHelper::toDateTime($order['dateOrdered']);

                $orders[$key]['Order Number'] = substr($order['number'], 0, 7);
                $orders[$key]['Product Revenue'] = number_format($productRevenue, 2);
                $orders[$key]['Tax'] = number_format($totalTax, 2);
                $orders[$key]['Shipping'] = number_format($totalShipping, 2);
                $orders[$key]['Total Revenue'] = number_format($orders[$key]['totalPaid'], 2);
                $orders[$key]['Date Ordered'] = $dateOrdered->format('Y-m-d H:i:s');

                unset(
                    $orders[$key]['number'],
                    $orders[$key]['orderId'],
                    $orders[$key]['totalPaid'],
                    $orders[$key]['dateOrdered']
                );
            }
        }

        return $orders;
    }

    /**
     * Calculate total tax and shipping include base values on orders table
     */
    private function getTotalAdjustmentByType($type, $order = null): ?string
    {
        $orderId = $order['orderId'] ?? null;

        $query = (new Query());
        $query->select('SUM([[orderadjustments.amount]])')
            ->from('{{%commerce_orders}} as orders')
            ->leftJoin(
                '{{%commerce_orderadjustments}} as orderadjustments',
                '[[orders.id]] = [[orderadjustments.orderId]]'
            )
            ->where("[[orderadjustments.type]] = '$type'");

        if ($orderId !== null) {
            // For Line Item Order History Report
            $query->andWhere(['[[orderadjustments.orderId]]' => $orderId]);
        } else {
            // For Aggregate Order History Report
            $startDate = $this->getStartDate();
            $endDate = $this->getEndDate();

            if ($startDate && $endDate) {
                $query->andWhere(['>=', 'orders.dateOrdered', $startDate->format('Y-m-d H:i:s')]);
                $query->andWhere(['<=', 'orders.dateOrdered', $endDate->format('Y-m-d H:i:s')]);
            }
        }

        return $query->scalar();
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        return array_merge($rules, $this->defineDateRangeRules());
    }
}
