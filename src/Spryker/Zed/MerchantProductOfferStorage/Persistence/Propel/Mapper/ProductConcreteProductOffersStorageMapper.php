<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\MerchantProductOfferStorage\Persistence\Propel\Mapper;

use Generated\Shared\Transfer\ProductConcreteProductOffersStorageTransfer;
use Orm\Zed\MerchantProductOfferStorage\Persistence\SpyProductConcreteProductOffersStorage;

class ProductConcreteProductOffersStorageMapper implements ProductConcreteProductOffersStorageMapperInterface
{
    /**
     * @param \Generated\Shared\Transfer\ProductConcreteProductOffersStorageTransfer $productConcreteProductOffersStorageTransfer
     * @param \Orm\Zed\MerchantProductOfferStorage\Persistence\SpyProductConcreteProductOffersStorage $spyProductConcreteProductOffersStorage
     *
     * @return \Orm\Zed\MerchantProductOfferStorage\Persistence\SpyProductConcreteProductOffersStorage
     */
    public function mapProductConcreteProductOffersStorageTransferToProductConcreteProductOffersStorageEntity(
        ProductConcreteProductOffersStorageTransfer $productConcreteProductOffersStorageTransfer,
        SpyProductConcreteProductOffersStorage $spyProductConcreteProductOffersStorage
    ): SpyProductConcreteProductOffersStorage {
        $spyProductConcreteProductOffersStorage->fromArray(
            $productConcreteProductOffersStorageTransfer->modifiedToArray(false)
        );

        return $spyProductConcreteProductOffersStorage;
    }

    /**
     * @param \Orm\Zed\MerchantProductOfferStorage\Persistence\SpyProductConcreteProductOffersStorage $spyProductConcreteProductOffersStorage
     * @param \Generated\Shared\Transfer\ProductConcreteProductOffersStorageTransfer $productConcreteProductOffersStorageTransfer
     *
     * @return \Generated\Shared\Transfer\ProductConcreteProductOffersStorageTransfer
     */
    public function mapProductConcreteProductOffersStorageEntityToProductConcreteProductOffersStorageTransfer(
        SpyProductConcreteProductOffersStorage $spyProductConcreteProductOffersStorage,
        ProductConcreteProductOffersStorageTransfer $productConcreteProductOffersStorageTransfer
    ): ProductConcreteProductOffersStorageTransfer {
        $productConcreteProductOffersStorageTransfer = $productConcreteProductOffersStorageTransfer->fromArray(
            $spyProductConcreteProductOffersStorage->toArray(),
            true
        );

        return $productConcreteProductOffersStorageTransfer;
    }
}
