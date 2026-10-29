<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\MerchantProductOfferStorage\Persistence;

use Generated\Shared\Transfer\ProductOfferTransfer;
use Orm\Zed\Merchant\Persistence\Map\SpyMerchantTableMap;
use Orm\Zed\ProductOffer\Persistence\Map\SpyProductOfferTableMap;
use Orm\Zed\ProductOffer\Persistence\SpyProductOfferQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Criterion\AbstractCriterion;
use Spryker\Zed\Kernel\Persistence\AbstractRepository;
use Spryker\Zed\PropelOrm\Business\Model\Formatter\OptimizedSimpleArrayFormatter;

/**
 * @method \Spryker\Zed\MerchantProductOfferStorage\Persistence\MerchantProductOfferStoragePersistenceFactory getFactory()
 */
class MerchantProductOfferStorageRepository extends AbstractRepository implements MerchantProductOfferStorageRepositoryInterface
{
    /**
     * @var int
     */
    protected const PRODUCT_OFFER_BATCH_SIZE = 1000;

    /**
     * @param array<int> $merchantIds
     *
     * @return \Generator<int, array<string>>
     */
    public function getProductConcreteSkusByMerchantIds(array $merchantIds): iterable
    {
        $productOfferPropelQuery = $this->getFactory()->getProductOfferPropelQuery();
        $productOfferPropelQuery
            ->addJoin(SpyProductOfferTableMap::COL_MERCHANT_REFERENCE, SpyMerchantTableMap::COL_MERCHANT_REFERENCE, Criteria::INNER_JOIN)
            ->addAnd($productOfferPropelQuery->getNewCriterion(SpyMerchantTableMap::COL_ID_MERCHANT, $merchantIds, Criteria::IN))
            ->orderByIdProductOffer();

        $lastIdProductOfferInBatch = 0;
        $limit = static::PRODUCT_OFFER_BATCH_SIZE;
        do {
            $data = $productOfferPropelQuery
                ->filterByIdProductOffer($lastIdProductOfferInBatch, Criteria::GREATER_THAN)
                ->select([SpyProductOfferTableMap::COL_ID_PRODUCT_OFFER, SpyProductOfferTableMap::COL_CONCRETE_SKU])
                ->setLimit($limit)
                ->distinct()
                ->find()
                ->getData();

            if ($data) {
                $lastIdProductOfferInBatch = max(
                    array_column($data, SpyProductOfferTableMap::COL_ID_PRODUCT_OFFER),
                );

                yield array_column($data, SpyProductOfferTableMap::COL_CONCRETE_SKU);
            }
        } while ($data);
    }

    /**
     * @param array<int> $merchantIds
     *
     * @return iterable<array<string>>
     */
    public function iterateProductOfferReferencesByMerchantIds(array $merchantIds, int $minIdProductOffer = 0, int $total = 1000): iterable
    {
        foreach ($this->getMerchantReferencesByMerchantIds($merchantIds) as $merchantReference) {
            $lastIdProductOffer = $minIdProductOffer;

            do {
                /** @var array<array<string, mixed>> $productOfferRows */
                $productOfferRows = $this->getFactory()
                    ->getProductOfferPropelQuery()
                    ->filterByMerchantReference($merchantReference)
                    ->filterByIdProductOffer($lastIdProductOffer, Criteria::GREATER_THAN)
                    ->select([SpyProductOfferTableMap::COL_ID_PRODUCT_OFFER, SpyProductOfferTableMap::COL_PRODUCT_OFFER_REFERENCE])
                    ->orderByIdProductOffer()
                    ->setLimit($total)
                    ->setFormatter(new OptimizedSimpleArrayFormatter())
                    ->find()
                    ->getData();

                if (!$productOfferRows) {
                    break;
                }

                $lastIdProductOffer = (int)end($productOfferRows)[SpyProductOfferTableMap::COL_ID_PRODUCT_OFFER];

                yield array_column($productOfferRows, SpyProductOfferTableMap::COL_PRODUCT_OFFER_REFERENCE);
            } while (count($productOfferRows) === $total);
        }
    }

    /**
     * @return iterable<list<int>>
     */
    public function iterateProductOfferIdsByMerchantReference(string $merchantReference, int $batchSize): iterable
    {
        $lastIdProductOffer = 0;

        do {
            /** @var list<int> $productOfferIds */
            $productOfferIds = $this->getFactory()
                ->getProductOfferPropelQuery()
                ->filterByMerchantReference($merchantReference)
                ->filterByIdProductOffer($lastIdProductOffer, Criteria::GREATER_THAN)
                ->select([SpyProductOfferTableMap::COL_ID_PRODUCT_OFFER])
                ->orderByIdProductOffer()
                ->setLimit($batchSize)
                ->find()
                ->getData();

            if (!$productOfferIds) {
                break;
            }

            $productOfferIds = array_map('intval', $productOfferIds);
            $lastIdProductOffer = end($productOfferIds);

            yield $productOfferIds;
        } while (count($productOfferIds) === $batchSize);
    }

    /**
     * @param list<array{int, int}> $productOfferIdRanges
     *
     * @return list<\Generated\Shared\Transfer\ProductOfferTransfer>
     */
    public function getProductOffersByMerchantReferenceAndProductOfferIdRanges(string $merchantReference, array $productOfferIdRanges): array
    {
        if (!$productOfferIdRanges) {
            return [];
        }

        $productOfferQuery = $this->getFactory()
            ->getProductOfferPropelQuery()
            ->filterByMerchantReference($merchantReference);

        /** @var array<array<string, mixed>> $productOfferRows */
        $productOfferRows = $productOfferQuery
            ->addAnd($this->createProductOfferIdRangesCriterion($productOfferQuery, $productOfferIdRanges))
            ->select([
                SpyProductOfferTableMap::COL_ID_PRODUCT_OFFER,
                SpyProductOfferTableMap::COL_PRODUCT_OFFER_REFERENCE,
                SpyProductOfferTableMap::COL_CONCRETE_SKU,
            ])
            ->setFormatter(new OptimizedSimpleArrayFormatter())
            ->find()
            ->getData();

        $productOfferTransfers = [];
        foreach ($productOfferRows as $productOfferRow) {
            $productOfferTransfers[] = (new ProductOfferTransfer())
                ->setIdProductOffer((int)$productOfferRow[SpyProductOfferTableMap::COL_ID_PRODUCT_OFFER])
                ->setProductOfferReference($productOfferRow[SpyProductOfferTableMap::COL_PRODUCT_OFFER_REFERENCE])
                ->setConcreteSku($productOfferRow[SpyProductOfferTableMap::COL_CONCRETE_SKU]);
        }

        return $productOfferTransfers;
    }

    /**
     * @param list<array{int, int}> $productOfferIdRanges
     */
    protected function createProductOfferIdRangesCriterion(SpyProductOfferQuery $productOfferQuery, array $productOfferIdRanges): AbstractCriterion
    {
        [$fromIdProductOffer, $toIdProductOffer] = $productOfferIdRanges[0];
        $productOfferIdRangesCriterion = $this->createProductOfferIdRangeCriterion($productOfferQuery, $fromIdProductOffer, $toIdProductOffer);

        foreach (array_slice($productOfferIdRanges, 1) as [$fromIdProductOffer, $toIdProductOffer]) {
            $productOfferIdRangesCriterion->addOr($this->createProductOfferIdRangeCriterion($productOfferQuery, $fromIdProductOffer, $toIdProductOffer));
        }

        return $productOfferIdRangesCriterion;
    }

    protected function createProductOfferIdRangeCriterion(
        SpyProductOfferQuery $productOfferQuery,
        int $fromIdProductOffer,
        int $toIdProductOffer
    ): AbstractCriterion {
        return $productOfferQuery
            ->getNewCriterion(SpyProductOfferTableMap::COL_ID_PRODUCT_OFFER, $fromIdProductOffer, Criteria::GREATER_EQUAL)
            ->addAnd($productOfferQuery->getNewCriterion(SpyProductOfferTableMap::COL_ID_PRODUCT_OFFER, $toIdProductOffer, Criteria::LESS_EQUAL));
    }

    /**
     * @param array<int> $merchantIds
     *
     * @return list<string>
     */
    public function getMerchantReferencesByMerchantIds(array $merchantIds): array
    {
        if (!$merchantIds) {
            return [];
        }

        /** @var list<string> $merchantReferences */
        $merchantReferences = $this->getFactory()
            ->getMerchantPropelQuery()
            ->filterByIdMerchant_In($merchantIds)
            ->select([SpyMerchantTableMap::COL_MERCHANT_REFERENCE])
            ->find()
            ->getData();

        return $merchantReferences;
    }
}
