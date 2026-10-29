<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\MerchantProductOfferStorage\Business\Writer;

use Generated\Shared\Transfer\EventEntityTransfer;
use Spryker\Zed\MerchantProductOfferStorage\Business\Reader\ProductOfferRangeReaderInterface;
use Spryker\Zed\MerchantProductOfferStorage\Dependency\Facade\MerchantProductOfferStorageToProductOfferStorageFacadeInterface;

class ProductConcreteProductOffersRangeStorageWriter implements ProductOfferRangeStorageWriterInterface
{
    /**
     * @uses \Orm\Zed\ProductOffer\Persistence\Map\SpyProductOfferTableMap::COL_CONCRETE_SKU
     */
    protected const string COL_CONCRETE_SKU = 'spy_product_offer.concrete_sku';

    // Each SKU is rebuilt with the product offers of all merchants, so one batch reads far more offers than it has SKUs.
    protected const int PRODUCT_CONCRETE_SKU_WRITE_BATCH_SIZE = 200;

    public function __construct(
        protected ProductOfferRangeReaderInterface $productOfferRangeReader,
        protected MerchantProductOfferStorageToProductOfferStorageFacadeInterface $productOfferStorageFacade
    ) {
    }

    /**
     * @param array<\Generated\Shared\Transfer\EventEntityTransfer> $rangeEventEntityTransfers
     */
    public function writeCollectionByRangeEvents(array $rangeEventEntityTransfers): void
    {
        $productConcreteSkus = [];

        foreach ($this->productOfferRangeReader->getProductOffersByRangeEvents($rangeEventEntityTransfers) as $productOfferTransfer) {
            $productConcreteSkus[$productOfferTransfer->getConcreteSkuOrFail()] = true;
        }

        $productEventEntityTransfers = [];

        foreach (array_keys($productConcreteSkus) as $productConcreteSku) {
            $productEventEntityTransfers[] = (new EventEntityTransfer())
                ->setAdditionalValues([static::COL_CONCRETE_SKU => (string)$productConcreteSku]);
        }

        foreach (array_chunk($productEventEntityTransfers, max(1, static::PRODUCT_CONCRETE_SKU_WRITE_BATCH_SIZE)) as $productEventEntityTransfersBatch) {
            $this->productOfferStorageFacade->writeProductConcreteProductOffersStorageCollectionByProductEvents($productEventEntityTransfersBatch);
        }
    }
}
