<?php

namespace BarrelStrength\Sprout\forms\fields\address;

use BarrelStrength\Sprout\forms\db\SproutTable;
use BarrelStrength\Sprout\forms\fields\address\Address as AddressModel;
use barrelstrength\sprout\forms\formfields\FormField;
use Craft;
use craft\base\Component;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\FieldInterface;
use craft\db\Query;
use craft\db\Table;
use Throwable;
use yii\db\Transaction;

class Addresses extends Component
{
    public const DEFAULT_COUNTRY = 'US';

    public const DEFAULT_LANGUAGE = 'en';

    public function saveAddress(FieldInterface $field, ElementInterface $element, bool $isNew): bool
    {
        /** @var Element $element */
        /** @var Field|FormField|AddressFieldTrait $field */
        $address = $element->getFieldValue($field->handle);

        // If we don't have an address model, delete the old address associated with this field
        if (!$address instanceof AddressModel) {
            Craft::$app->getDb()->createCommand()
                ->delete(SproutTable::ADDRESSES, [
                    'elementId' => $element->getId(),
                    'siteId' => $element->siteId,
                    'fieldId' => $field->id,
                ])
                ->execute();

            return true;
        }

        // If the user cleared the address, delete it if it exists and don't save anything
        if ($deletedAddressId = $field->getDeletedAddressId()) {
            $this->deleteAddressById($deletedAddressId);

            return true;
        }

        if (!$isNew) {
            $record = AddressRecord::findOne([
                'elementId' => $element->getId(),
                'siteId' => $element->siteId,
                'fieldId' => $field->id,
            ]);
        } else {
            $record = new AddressRecord();
        }

        $record->elementId = $element->getId();
        $record->siteId = $element->siteId;
        $record->fieldId = $field->id;
        $record->countryCode = $address->countryCode;
        $record->administrativeAreaCode = $address->administrativeAreaCode;
        $record->locality = $address->locality;
        $record->dependentLocality = $address->dependentLocality;
        $record->postalCode = $address->postalCode;
        $record->sortingCode = $address->sortingCode;
        $record->address1 = $address->address1;
        $record->address2 = $address->address2;

        if (!$address->validate()) {
            return false;
        }

        /** @var Transaction $transaction */
        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            $record->save();

            $address->id = $record->id;

            $this->deleteUnusedAddresses();

            $transaction->commit();

            return true;
        } catch (Throwable $throwable) {
            $transaction->rollBack();
            throw $throwable;
        }
    }

    public function duplicateAddress(FieldInterface $field, ElementInterface $target, bool $isNew): void
    {
        /** @var Transaction $transaction */
        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            $this->saveAddress($field, $target, $isNew);

            $transaction->commit();
        } catch (Throwable $throwable) {
            $transaction->rollBack();
            throw $throwable;
        }
    }

    /**
     * Deletes any addresses that are found that no longer match an existing Element ID
     */
    public function deleteUnusedAddresses(): void
    {
        $addressIdsWithDeletedElementIds = (new Query())
            ->select('addresses.id')
            ->from(['addresses' => SproutTable::ADDRESSES])
            ->leftJoin(Table::ELEMENTS . ' elements', '[[addresses.elementId]] = [[elements.id]]')
            ->where(['elements.id' => null])
            ->column();

        Craft::$app->db->createCommand()
            ->delete(SproutTable::ADDRESSES, [
                'id' => $addressIdsWithDeletedElementIds,
            ])
            ->execute();
    }

    public function getAddressById($id): ?AddressModel
    {
        $result = (new Query())
            ->select([
                'id',
                'elementId',
                'siteId',
                'fieldId',
                'countryCode',
                'administrativeAreaCode',
                'locality',
                'dependentLocality',
                'postalCode',
                'sortingCode',
                'address1',
                'address2',
            ])
            ->from([SproutTable::ADDRESSES])
            ->where(['id' => $id])
            ->one();

        return $result ? new AddressModel($result) : null;
    }

    public function getAddressFromElement(ElementInterface $element, $fieldId): ?AddressModel
    {
        $elementId = $element->id ?? null;

        if (!$elementId) {
            return null;
        }

        /** @var Element $element */
        $query = (new Query())
            ->select([
                'id',
                'elementId',
                'siteId',
                'fieldId',
                'countryCode',
                'administrativeAreaCode',
                'locality',
                'dependentLocality',
                'postalCode',
                'sortingCode',
                'address1',
                'address2',
            ])
            ->from([SproutTable::ADDRESSES])
            ->where([
                'siteId' => $element->siteId,
                'fieldId' => $fieldId,
            ]);

        if ($element->getId()) {
            $query->andWhere(['elementId' => $element->getId()]);
        }

        $result = $query->one();

        return $result ? new AddressModel($result) : null;
    }

    public function deleteAddressById($id): bool|int
    {
        $record = AddressRecord::findOne($id);

        if ($record !== null) {
            return $record->delete();
        }

        return false;
    }
}
