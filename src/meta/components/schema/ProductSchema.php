<?php

namespace BarrelStrength\Sprout\meta\components\schema;

use craft\commerce\elements\Product;
use craft\commerce\Plugin as Commerce;

class ProductSchema extends ThingSchema
{
    public function getName(): string
    {
        return 'Product';
    }

    public function getType(): string
    {
        return 'Product';
    }

    public function isUnlistedSchemaType(): bool
    {
        return false;
    }

    public function addProperties(): void
    {
        parent::addProperties();

        if ($this->element instanceof Product) {
            $this->addProductProperties();
        }
    }

    /**
     */
    public function addProductProperties(): void
    {
        $identity = $this->globals['identity'];

        /**
         * @var Product $element
         */
        $element = $this->element;
        $seller = null;

        $websiteIdentity = [
            'Person' => WebsiteIdentityPersonSchema::class,
            'Organization' => WebsiteIdentityOrganizationSchema::class,
        ];

        /** @var Commerce $commerce */
        $commerce = Commerce::getInstance();
        $primaryCurrencyIso = $commerce->getPaymentCurrencies()->getPrimaryPaymentCurrencyIso();

        $offers = [];
        $identityType = $identity['@type'];

        if (isset($websiteIdentity[$identityType])) {
            // Determine if we have an Organization or Person Schema Type
            $schemaModel = $websiteIdentity[$identityType];

            /**
             * @var WebsiteIdentityOrganizationSchema|WebsiteIdentityPersonSchema $identitySchema
             */
            $identitySchema = new $schemaModel();
            $identitySchema->globals = $this->globals;
            $seller = $identitySchema->getSchema();
        }

        foreach ($element->getVariants() as $variant) {
            $offers[$variant->id]['@type'] = 'Offer';
            $offers[$variant->id]['sku'] = $variant->sku;
            $offers[$variant->id]['price'] = $variant->price;
            $offers[$variant->id]['priceCurrency'] = $primaryCurrencyIso;

            if ($variant->hasUnlimitedStock == 1 || $variant->stock > 0) {
                $availability = 'https://schema.org/InStock';
            } else {
                $availability = 'https://schema.org/OutOfStock';
            }

            $offers[$variant->id]['availability'] = $availability;
            $offers[$variant->id]['seller'] = $seller;
        }

        $this->addProperty('offers', array_values($offers));
    }
}
