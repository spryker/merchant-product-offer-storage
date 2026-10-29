<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\MerchantProductOfferStorage\Business\Writer;

use Generated\Shared\Transfer\EventEntityTransfer;
use Spryker\Zed\MerchantProductOfferStorage\Business\Reader\ProductOfferRangeReaderInterface;
use Spryker\Zed\MerchantProductOfferStorage\Dependency\Facade\MerchantProductOfferStorageToProductOfferStorageFacadeInterface;
use Spryker\Zed\MerchantProductOfferStorage\MerchantProductOfferStorageConfig;

class ProductOfferRangeStorageWriter implements ProductOfferRangeStorageWriterInterface
{
    /**
     * @uses \Orm\Zed\ProductOffer\Persistence\Map\SpyProductOfferTableMap::COL_PRODUCT_OFFER_REFERENCE
     */
    protected const string COL_PRODUCT_OFFER_REFERENCE = 'spy_product_offer.product_offer_reference';

    public function __construct(
        protected ProductOfferRangeReaderInterface $productOfferRangeReader,
        protected MerchantProductOfferStorageToProductOfferStorageFacadeInterface $productOfferStorageFacade,
        protected MerchantProductOfferStorageConfig $merchantProductOfferStorageConfig
    ) {
    }

    /**
     * @param array<\Generated\Shared\Transfer\EventEntityTransfer> $rangeEventEntityTransfers
     */
    public function writeCollectionByRangeEvents(array $rangeEventEntityTransfers): void
    {
        $productOfferEventEntityTransfers = [];

        foreach ($this->productOfferRangeReader->getProductOffersByRangeEvents($rangeEventEntityTransfers) as $productOfferTransfer) {
            $productOfferEventEntityTransfers[] = (new EventEntityTransfer())
                ->setAdditionalValues([static::COL_PRODUCT_OFFER_REFERENCE => $productOfferTransfer->getProductOfferReferenceOrFail()]);
        }

        $batchSize = max(1, $this->merchantProductOfferStorageConfig->getWriteCollectionByMerchantEventsBatchSize());

        foreach (array_chunk($productOfferEventEntityTransfers, $batchSize) as $productOfferEventEntityTransfersBatch) {
            $this->productOfferStorageFacade->writeProductOfferStorageCollectionByProductOfferEvents($productOfferEventEntityTransfersBatch);
        }
    }
}
