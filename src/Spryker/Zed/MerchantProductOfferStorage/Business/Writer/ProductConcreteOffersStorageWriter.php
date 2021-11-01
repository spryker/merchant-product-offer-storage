<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\MerchantProductOfferStorage\Business\Writer;

use Generated\Shared\Transfer\ProductOfferCollectionTransfer;
use Orm\Zed\ProductOffer\Persistence\Map\SpyProductOfferTableMap;
use Spryker\Zed\MerchantProductOfferStorage\Business\Deleter\ProductConcreteOffersStorageDeleterInterface;
use Spryker\Zed\MerchantProductOfferStorage\Dependency\Facade\MerchantProductOfferStorageToEventBehaviorFacadeInterface;
use Spryker\Zed\MerchantProductOfferStorage\Dependency\Facade\MerchantProductOfferStorageToStoreFacadeInterface;
use Spryker\Zed\MerchantProductOfferStorage\Persistence\MerchantProductOfferStorageEntityManagerInterface;
use Spryker\Zed\MerchantProductOfferStorage\Persistence\MerchantProductOfferStorageRepositoryInterface;

class ProductConcreteOffersStorageWriter implements ProductConcreteOffersStorageWriterInterface
{
    /**
     * @var array<mixed>
     */
    public static $storeNames = [];

    /**
     * @uses \Spryker\Shared\ProductOffer\ProductOfferConfig::STATUS_APPROVED
     *
     * @var string
     */
    public const STATUS_APPROVED = 'approved';

    /**
     * @var \Spryker\Zed\MerchantProductOfferStorage\Dependency\Facade\MerchantProductOfferStorageToEventBehaviorFacadeInterface
     */
    protected $eventBehaviorFacade;

    /**
     * @var \Spryker\Zed\MerchantProductOfferStorage\Persistence\MerchantProductOfferStorageEntityManagerInterface
     */
    protected $merchantProductOfferStorageEntityManager;

    /**
     * @var \Spryker\Zed\MerchantProductOfferStorage\Persistence\MerchantProductOfferStorageRepositoryInterface
     */
    protected $merchantProductOfferStorageRepository;

    /**
     * @var \Spryker\Zed\MerchantProductOfferStorage\Business\Deleter\ProductConcreteOffersStorageDeleterInterface
     */
    protected $productConcreteOffersStorageDeleter;

    /**
     * @var \Spryker\Zed\MerchantProductOfferStorage\Dependency\Facade\MerchantProductOfferStorageToStoreFacadeInterface
     */
    protected $storeFacade;

    /**
     * @var \Spryker\Zed\MerchantProductOfferStorage\Business\Writer\ProductOfferCriteriaTransferProviderInterface
     */
    protected $productOfferCriteriaTransferProvider;

    /**
     * @var array<string>
     */
    protected $storeNamesList;

    /**
     * @param \Spryker\Zed\MerchantProductOfferStorage\Dependency\Facade\MerchantProductOfferStorageToEventBehaviorFacadeInterface $eventBehaviorFacade
     * @param \Spryker\Zed\MerchantProductOfferStorage\Persistence\MerchantProductOfferStorageEntityManagerInterface $merchantProductOfferStorageEntityManager
     * @param \Spryker\Zed\MerchantProductOfferStorage\Persistence\MerchantProductOfferStorageRepositoryInterface $merchantProductOfferStorageRepository
     * @param \Spryker\Zed\MerchantProductOfferStorage\Business\Deleter\ProductConcreteOffersStorageDeleterInterface $productConcreteOffersStorageDeleter
     * @param \Spryker\Zed\MerchantProductOfferStorage\Dependency\Facade\MerchantProductOfferStorageToStoreFacadeInterface $storeFacade
     * @param \Spryker\Zed\MerchantProductOfferStorage\Business\Writer\ProductOfferCriteriaTransferProviderInterface $productOfferCriteriaTransferProvider
     */
    public function __construct(
        MerchantProductOfferStorageToEventBehaviorFacadeInterface $eventBehaviorFacade,
        MerchantProductOfferStorageEntityManagerInterface $merchantProductOfferStorageEntityManager,
        MerchantProductOfferStorageRepositoryInterface $merchantProductOfferStorageRepository,
        ProductConcreteOffersStorageDeleterInterface $productConcreteOffersStorageDeleter,
        MerchantProductOfferStorageToStoreFacadeInterface $storeFacade,
        ProductOfferCriteriaTransferProviderInterface $productOfferCriteriaTransferProvider
    ) {
        $this->eventBehaviorFacade = $eventBehaviorFacade;
        $this->merchantProductOfferStorageEntityManager = $merchantProductOfferStorageEntityManager;
        $this->merchantProductOfferStorageRepository = $merchantProductOfferStorageRepository;
        $this->productConcreteOffersStorageDeleter = $productConcreteOffersStorageDeleter;
        $this->storeFacade = $storeFacade;
        $this->productOfferCriteriaTransferProvider = $productOfferCriteriaTransferProvider;
    }

    /**
     * @param array<\Generated\Shared\Transfer\EventEntityTransfer> $eventTransfers
     *
     * @return void
     */
    public function writeCollectionByMerchantEvents(array $eventTransfers): void
    {
        $merchantIds = $this->eventBehaviorFacade->getEventTransferIds($eventTransfers);

        if (!$merchantIds) {
            return;
        }

        $productSkus = $this->merchantProductOfferStorageRepository->getProductConcreteSkusByMerchantIds($merchantIds);

        $this->writeCollectionByProductSkus($productSkus);
    }

    /**
     * @param array<\Generated\Shared\Transfer\EventEntityTransfer> $eventTransfers
     *
     * @return void
     */
    public function writeCollectionByProductSkuEvents(array $eventTransfers): void
    {
        /** @var array<string> $productSkus */
        $productSkus = $this->eventBehaviorFacade->getEventTransfersAdditionalValues($eventTransfers, SpyProductOfferTableMap::COL_CONCRETE_SKU);

        $this->writeCollectionByProductSkus($productSkus);
    }

    /**
     * @param array<string> $productConcreteSkus
     *
     * @return void
     */
    protected function writeCollectionByProductSkus(array $productConcreteSkus): void
    {
        if (count($productConcreteSkus) === 0) {
            return;
        }

        $productConcreteSkus = array_unique($productConcreteSkus);

        $productOfferCriteriaTransfer = $this->productOfferCriteriaTransferProvider->createSellableProductOfferCriteriaTransfer()
            ->setConcreteSkus($productConcreteSkus);
        $productOfferCollectionTransfer = $this->merchantProductOfferStorageRepository->getProductOffers($productOfferCriteriaTransfer);

        $productOfferReferencesGroupedByConcreteSku = $this->getProductOfferReferencesGroupedByConcreteSku(
            $productConcreteSkus,
            $productOfferCollectionTransfer,
        );

        foreach ($productOfferReferencesGroupedByConcreteSku as $concreteSku => $productOfferReferencesGroupedByStore) {
            $storeNamesToRemove = [];

            foreach ($productOfferReferencesGroupedByStore as $storeName => $productOfferReferenceList) {
                if (!$productOfferReferenceList) {
                    $storeNamesToRemove[] = $storeName;

                    continue;
                }
                $this->merchantProductOfferStorageEntityManager->saveProductConcreteProductOffers($concreteSku, $productOfferReferenceList, $storeName);
            }

            if ($storeNamesToRemove) {
                $this->deleteProductConcreteProductOffers($storeNamesToRemove, $concreteSku);
            }
        }
    }

    /**
     * @param array<string> $productConcreteSkus
     * @param \Generated\Shared\Transfer\ProductOfferCollectionTransfer $productOfferCollectionTransfer
     *
     * @return array<mixed>
     */
    protected function getProductOfferReferencesGroupedByConcreteSku(
        array $productConcreteSkus,
        ProductOfferCollectionTransfer $productOfferCollectionTransfer
    ): array {
        $productOfferReferencesGroupedByConcreteSku = [];
        foreach ($productConcreteSkus as $productConcreteSku) {
            foreach ($this->getStoreNamesList() as $storeName) {
                $productOfferReferencesGroupedByConcreteSku[$productConcreteSku][$storeName] = [];
            }
        }

        foreach ($productOfferCollectionTransfer->getProductOffers() as $productOfferTransfer) {
            if (!isset($productOfferReferencesGroupedByConcreteSku[$productOfferTransfer->getConcreteSku()])) {
                $productOfferReferencesGroupedByConcreteSku[$productOfferTransfer->getConcreteSku()] = [];
            }
            foreach ($productOfferTransfer->getStores() as $storeTransfer) {
                $productOfferReferencesGroupedByConcreteSku[$productOfferTransfer->getConcreteSku()][$storeTransfer->getName()][] =
                    mb_strtolower($productOfferTransfer->getProductOfferReference());
            }
        }

        return $productOfferReferencesGroupedByConcreteSku;
    }

    /**
     * @param array<string> $storeNamesToRemove
     * @param string $productSku
     *
     * @return void
     */
    protected function deleteProductConcreteProductOffers(array $storeNamesToRemove, string $productSku): void
    {
        foreach ($storeNamesToRemove as $storeName) {
            $this->productConcreteOffersStorageDeleter->deleteCollectionByProductSkus(
                [$productSku],
                $storeName,
            );
        }
    }

    /**
     * @return array<string>
     */
    protected function getStoreNamesList(): array
    {
        if ($this->storeNamesList) {
            return $this->storeNamesList;
        }

        foreach ($this->storeFacade->getAllStores() as $storeTransfer) {
            $this->storeNamesList[] = $storeTransfer->getName();
        }

        return $this->storeNamesList;
    }
}
