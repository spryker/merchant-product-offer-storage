<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\MerchantProductOfferStorage;

use Spryker\Zed\Kernel\AbstractBundleConfig;

class MerchantProductOfferStorageConfig extends AbstractBundleConfig
{
    /**
     * @var int
     */
    protected const WRITE_COLLECTION_BY_MERCHANT_EVENTS_BATCH_SIZE = 1000;

    protected const int MERCHANT_PRODUCT_OFFER_RANGE_SIZE = 20;

    protected const int MERCHANT_PRODUCT_OFFER_RANGE_READ_BATCH_SIZE = 1000;

    /**
     * @uses \Orm\Zed\Merchant\Persistence\Map\SpyMerchantTableMap::COL_IS_ACTIVE
     */
    protected const string COL_MERCHANT_IS_ACTIVE = 'spy_merchant.is_active';

    /**
     * @uses \Orm\Zed\Merchant\Persistence\Map\SpyMerchantTableMap::COL_STATUS
     */
    protected const string COL_MERCHANT_STATUS = 'spy_merchant.status';

    /**
     * Specification:
     * - Returns the number of product offers in the batch to be written by merchant events.
     *
     * @api
     */
    public function getWriteCollectionByMerchantEventsBatchSize(): int
    {
        return static::WRITE_COLLECTION_BY_MERCHANT_EVENTS_BATCH_SIZE;
    }

    /**
     * Specification:
     * - Returns the `spy_merchant` columns that change the product offer storage data.
     * - A merchant event that has modified columns republishes the product offers of the merchant only when one of these columns changed.
     * - A merchant event without modified columns always republishes the product offers of the merchant.
     *
     * @api
     *
     * @return list<string>
     */
    public function getProductOfferStorageRelevantMerchantColumns(): array
    {
        return [
            static::COL_MERCHANT_IS_ACTIVE,
            static::COL_MERCHANT_STATUS,
        ];
    }

    /**
     * Specification:
     * - Returns the number of product offers in one range event that a merchant event triggers.
     * - The handler of one range event writes the storage of up to this number of product offers.
     * - A value below 1 counts as 1.
     *
     * @api
     */
    public function getMerchantProductOfferRangeSize(): int
    {
        return static::MERCHANT_PRODUCT_OFFER_RANGE_SIZE;
    }

    /**
     * Specification:
     * - Returns the number of product offer IDs that are read in one query to cut the product offers of a merchant into ranges.
     * - The range events of one query are triggered in one bulk.
     * - The number is rounded down to a multiple of {@link getMerchantProductOfferRangeSize()}, and is at least one range.
     *
     * @api
     */
    public function getMerchantProductOfferRangeReadBatchSize(): int
    {
        return static::MERCHANT_PRODUCT_OFFER_RANGE_READ_BATCH_SIZE;
    }
}
