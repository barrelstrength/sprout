<?php

namespace BarrelStrength\Sprout\forms\fields\address;

use BarrelStrength\Sprout\forms\db\SproutTable;
use craft\db\ActiveRecord;
use craft\gql\types\DateTime;

/**
 * @property int $id
 * @property int $elementId
 * @property int $siteId
 * @property int $fieldId
 * @property string $countryCode
 * @property string $administrativeAreaCode
 * @property string $locality
 * @property string $dependentLocality
 * @property string $postalCode
 * @property string $sortingCode
 * @property string $address1
 * @property string $address2
 * @property DateTime $dateCreated
 * @property DateTime $dateUpdated
 * @property string $uid
 */
class AddressRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        // @todo - remove this once AddressRecord references are resolved
        // SproutTable::ADDRESSES
        return '{{%sprout_addresses}}';
    }
}
