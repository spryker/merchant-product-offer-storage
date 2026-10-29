<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\MerchantProductOfferStorage\Persistence;

interface MerchantProductOfferStorageRepositoryInterface
{
    /**
     * @param array<int> $merchantIds
     *
     * @return \Generator<int, array<string>>
     */
    public function getProductConcreteSkusByMerchantIds(array $merchantIds): iterable;

    /**
     * @param array<int> $merchantIds
     *
     * @return iterable<array<string>>
     */
    public function iterateProductOfferReferencesByMerchantIds(array $merchantIds, int $minIdProductOffer = 0, int $total = 1000): iterable;

    /**
     * @param array<int> $merchantIds
     *
     * @return list<string>
     */
    public function getMerchantReferencesByMerchantIds(array $merchantIds): array;

    /**
     * @return iterable<list<int>> Pages of up to `$batchSize` IDs, ordered by ID.
     */
    public function iterateProductOfferIdsByMerchantReference(string $merchantReference, int $batchSize): iterable;

    /**
     * @param list<array{int, int}> $productOfferIdRanges The first and the last product offer ID of each range, both inclusive.
     *
     * @return list<\Generated\Shared\Transfer\ProductOfferTransfer>
     */
    public function getProductOffersByMerchantReferenceAndProductOfferIdRanges(string $merchantReference, array $productOfferIdRanges): array;
}
