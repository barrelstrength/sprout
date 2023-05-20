<?php

namespace BarrelStrength\Sprout\datastudio\components\datasources;

use BarrelStrength\Sprout\datastudio\components\elements\DataSetElement;
use BarrelStrength\Sprout\datastudio\datasources\DataSource;
use BarrelStrength\Sprout\datastudio\datasources\DateRangeHelper;
use BarrelStrength\Sprout\datastudio\datasources\DateRangeInterface;
use BarrelStrength\Sprout\datastudio\datasources\DateRangeTrait;
use Craft;
use craft\commerce\elements\Product;
use craft\commerce\elements\Variant;
use craft\db\Query;
use craft\db\Table;
use DateTime;

class CommerceProductRevenueDataSource extends DataSource implements DateRangeInterface
{
    use DateRangeTrait;

    public bool $variants = true;

    public function datetimeAttributes(): array
    {
        return [
            'startDate',
            'endDate',
        ];
    }

    public static function getHandle(): string
    {
        return 'commerce-product-revenue';
    }

    public static function displayName(): string
    {
        return Craft::t('sprout-module-data-studio', 'Commerce Product Revenue');
    }

    public function getDescription(): string
    {
        return Craft::t('sprout-module-data-studio', 'Create sales reports for your products and variants.');
    }

    public function getSettingsHtml(): ?string
    {
        $dateRanges = DateRangeHelper::getDateRanges();

        return Craft::$app->getView()->renderTemplate('sprout-module-data-studio/_components/datasources/CommerceProductRevenue/settings.twig', [
            'defaultStartDate' => new DateTime(),
            'defaultEndDate' => new DateTime(),
            'dateRanges' => $dateRanges,
            'settings' => $this->getSettings(),
        ]);
    }

    public function getResults(DataSetElement $dataSet): array
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $query = (new Query());
        $query->select('
            [[variants.id]] as variantId,
            [[products.id]] as productId,
            SUM([[lineitems.total]]) as total,
            SUM([[lineitems.saleAmount]]) as saleAmount,
            SUM([[lineitems.salePrice]] * [[lineitems.qty]]) as productRevenue,
            SUM([[lineitems.qty]]) as quantitySold,
            [[variants.sku]] as SKU')
            ->from('{{%commerce_orders}} as orders')
            ->leftJoin('{{%commerce_lineitems}} as lineitems', '[[orders.id]] = [[lineitems.orderId]]')
            ->leftJoin('{{%commerce_variants}} as variants', '[[lineitems.purchasableId]] = [[variants.id]]')
            ->leftJoin('{{%commerce_products}} as products', '[[variants.productId]] = [[products.id]]')
            ->leftJoin(['elements' => Table::ELEMENTS], '[[orders.id]] = [[elements.id]]')
            ->where(['elements.dateDeleted' => null]);

        if ($startDate && $endDate) {
            $query->andWhere(['>=', '[[orders.dateOrdered]]', $startDate->format('Y-m-d H:i:s')]);
            $query->andWhere(['<=', '[[orders.dateOrdered]]', $endDate->format('Y-m-d H:i:s')]);
        }

        $query->groupBy('variantId');

        $query->orderBy(['[[products.id]]' => SORT_DESC]);

        $results = $query->all();

        $rows = [];
        if ($results) {
            foreach ($results as $key => $result) {
                //                if ($result['productId'] === null) {
                //                    continue;
                //                }
                #$lineItemId = $result['lineItemId'];
                #$lineItem = \craft\commerce\Plugin::getInstance()->lineItems->getLineItemById($lineItemId);

                $productId = $result['productId'];
                $variantId = $result['variantId'];
                $rows[$key]['Variant ID'] = $variantId ?? '–';
                $rows[$key]['Product ID'] = $productId ?? '–';
                $rows[$key]['Line Item Revenue'] = number_format($result['total'], 2);
                $rows[$key]['Sale Amount'] = number_format($result['saleAmount'], 2);
                #$rows[$key]['Shipping Cost'] = number_format($lineItem->getAdjustmentsTotalByType('shipping'), 2);
                #$rows[$key]['Tax'] = number_format($lineItem->getAdjustmentsTotalByType('tax'), 2);
                $rows[$key]['Product Revenue'] = number_format($result['productRevenue'], 2);
                $rows[$key]['Quantity Sold'] = $result['quantitySold'];
                $rows[$key]['SKU'] = $result['SKU'] ?? '–';

                /**@var $productElement Product */
                $productElement = $productId ? Craft::$app->elements->getElementById($productId) : null;

                if (!$this->variants) {
                    /**
                     * @var $variantElement Variant
                     */
                    $variantElement = $variantId ? Craft::$app->elements->getElementById($variantId) : null;

                    if ($variantElement) {
                        $rows[$key]['Variant Title'] = $variantElement->title;
                    } else {
                        $rows[$key]['Variant Title'] = Craft::t('sprout-module-data-studio', 'Variant has been deleted');
                    }
                }

                if ($productElement) {
                    $rows[$key]['Product Title'] = $productElement->title;
                } else {
                    $rows[$key]['Product Title'] = Craft::t('sprout-module-data-studio', 'Product has been deleted');
                }

                $rows[$key] = array_reverse($rows[$key], true);
            }
        }

        return $rows;
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        return array_merge($rules, $this->defineDateRangeRules());
    }
}
