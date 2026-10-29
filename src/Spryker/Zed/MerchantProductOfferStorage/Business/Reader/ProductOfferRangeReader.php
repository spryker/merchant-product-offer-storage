<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\MerchantProductOfferStorage\Business\Reader;

use Spryker\Shared\MerchantProductOfferStorage\MerchantProductOfferStorageConfig;
use Spryker\Zed\MerchantProductOfferStorage\Persistence\MerchantProductOfferStorageRepositoryInterface;

class ProductOfferRangeReader implements ProductOfferRangeReaderInterface
{
    protected const int PRODUCT_OFFER_ID_RANGES_PER_QUERY = 100;

    public function __construct(protected MerchantProductOfferStorageRepositoryInterface $merchantProductOfferStorageRepository)
    {
    }

    /**
     * @param array<\Generated\Shared\Transfer\EventEntityTransfer> $rangeEventEntityTransfers
     *
     * @return list<\Generated\Shared\Transfer\ProductOfferTransfer>
     */
    public function getProductOffersByRangeEvents(array $rangeEventEntityTransfers): array
    {
        $productOfferTransfers = [];

        foreach ($this->getProductOfferIdRangesGroupedByMerchantReference($rangeEventEntityTransfers) as $merchantReference => $productOfferIdRanges) {
            foreach (array_chunk($productOfferIdRanges, max(1, static::PRODUCT_OFFER_ID_RANGES_PER_QUERY)) as $productOfferIdRangesChunk) {
                $productOfferTransfers[] = $this->merchantProductOfferStorageRepository->getProductOffersByMerchantReferenceAndProductOfferIdRanges(
                    (string)$merchantReference,
                    $productOfferIdRangesChunk,
                );
            }
        }

        return array_merge(...$productOfferTransfers);
    }

    /**
     * @param array<\Generated\Shared\Transfer\EventEntityTransfer> $rangeEventEntityTransfers
     *
     * @return array<string, list<array{int, int}>>
     */
    protected function getProductOfferIdRangesGroupedByMerchantReference(array $rangeEventEntityTransfers): array
    {
        $productOfferIdRanges = [];

        foreach ($rangeEventEntityTransfers as $rangeEventEntityTransfer) {
            $additionalValues = $rangeEventEntityTransfer->getAdditionalValues();
            $productOfferIdRanges[(string)$additionalValues[MerchantProductOfferStorageConfig::RANGE_MERCHANT_REFERENCE]][] = [
                (int)$additionalValues[MerchantProductOfferStorageConfig::RANGE_FROM_ID_PRODUCT_OFFER],
                (int)$additionalValues[MerchantProductOfferStorageConfig::RANGE_TO_ID_PRODUCT_OFFER],
            ];
        }

        return $productOfferIdRanges;
    }
}
