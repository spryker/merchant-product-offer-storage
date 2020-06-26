<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

namespace Spryker\Client\MerchantProductOfferStorage\Storage;

use ArrayObject;
use Generated\Shared\Transfer\ProductOfferStorageCollectionTransfer;
use Generated\Shared\Transfer\ProductOfferStorageCriteriaTransfer;
use Generated\Shared\Transfer\ProductOfferStorageTransfer;
use Spryker\Client\MerchantProductOfferStorage\Dependency\Client\MerchantProductOfferStorageToMerchantStorageClientInterface;
use Spryker\Client\MerchantProductOfferStorage\Dependency\Client\MerchantProductOfferStorageToStorageClientInterface;
use Spryker\Client\MerchantProductOfferStorage\Dependency\Service\MerchantProductOfferStorageToUtilEncodingServiceInterface;
use Spryker\Client\MerchantProductOfferStorage\Mapper\MerchantProductOfferMapperInterface;
use Spryker\Client\MerchantProductOfferStorage\ProductConcreteDefaultProductOffer\ProductConcreteDefaultProductOfferInterface;
use Spryker\Client\MerchantProductOfferStorageExtension\Dependency\Plugin\ProductOfferStorageCollectionSorterPluginInterface;

class ProductOfferStorageReader implements ProductOfferStorageReaderInterface
{
    /**
     * @var \Spryker\Client\MerchantProductOfferStorage\Dependency\Client\MerchantProductOfferStorageToStorageClientInterface
     */
    protected $storageClient;

    /**
     * @var \Spryker\Client\MerchantProductOfferStorage\Dependency\Service\MerchantProductOfferStorageToSynchronizationServiceInterface
     */
    protected $synchronizationService;

    /**
     * @var \Spryker\Client\MerchantProductOfferStorage\Mapper\MerchantProductOfferMapperInterface $merchantProductOfferMapper
     */
    protected $merchantProductOfferMapper;

    /**
     * @var \Spryker\Client\MerchantProductOfferStorage\Dependency\Client\MerchantProductOfferStorageToStoreClientInterface
     */
    protected $storeClient;

    /**
     * @var \Spryker\Client\MerchantProductOfferStorage\Dependency\Client\MerchantProductOfferStorageToMerchantStorageClientInterface
     */
    protected $merchantStorageClient;

    /**
     * @var \Spryker\Client\MerchantProductOfferStorage\Dependency\Service\MerchantProductOfferStorageToUtilEncodingServiceInterface
     */
    protected $utilEncodingService;

    /**
     * @var \Spryker\Client\MerchantProductOfferStorage\Storage\ProductOfferStorageKeyGeneratorInterface
     */
    protected $productOfferStorageKeyGenerator;

    /**
     * @var \Spryker\Client\MerchantProductOfferStorage\ProductConcreteDefaultProductOffer\ProductConcreteDefaultProductOfferInterface
     */
    protected $productConcreteDefaultProductOffer;

    /**
     * @var \Spryker\Client\MerchantProductOfferStorageExtension\Dependency\Plugin\PriceProductOfferStorageExpanderPluginInterface[]
     */
    protected $priceProductOfferStorageExpanderPlugins;

    /**
     * @var \Spryker\Client\MerchantProductOfferStorageExtension\Dependency\Plugin\ProductOfferStorageCollectionSorterPluginInterface
     */
    protected $productOfferStorageCollectionSorterPlugin;

    /**
     * @param \Spryker\Client\MerchantProductOfferStorage\Dependency\Client\MerchantProductOfferStorageToStorageClientInterface $storageClient
     * @param \Spryker\Client\MerchantProductOfferStorage\Mapper\MerchantProductOfferMapperInterface $merchantProductOfferMapper
     * @param \Spryker\Client\MerchantProductOfferStorage\Dependency\Client\MerchantProductOfferStorageToMerchantStorageClientInterface $merchantStorageClient
     * @param \Spryker\Client\MerchantProductOfferStorage\Dependency\Service\MerchantProductOfferStorageToUtilEncodingServiceInterface $utilEncodingService
     * @param \Spryker\Client\MerchantProductOfferStorage\Storage\ProductOfferStorageKeyGeneratorInterface $productOfferStorageKeyGenerator
     * @param \Spryker\Client\MerchantProductOfferStorage\ProductConcreteDefaultProductOffer\ProductConcreteDefaultProductOfferInterface $productConcreteDefaultProductOffer
     * @param \Spryker\Client\MerchantProductOfferStorageExtension\Dependency\Plugin\ProductOfferStorageCollectionSorterPluginInterface $productOfferStorageCollectionSorterPlugin
     * @param \Spryker\Client\MerchantProductOfferStorageExtension\Dependency\Plugin\PriceProductOfferStorageExpanderPluginInterface[] $priceProductOfferStorageExpanderPlugins
     */
    public function __construct(
        MerchantProductOfferStorageToStorageClientInterface $storageClient,
        MerchantProductOfferMapperInterface $merchantProductOfferMapper,
        MerchantProductOfferStorageToMerchantStorageClientInterface $merchantStorageClient,
        MerchantProductOfferStorageToUtilEncodingServiceInterface $utilEncodingService,
        ProductOfferStorageKeyGeneratorInterface $productOfferStorageKeyGenerator,
        ProductConcreteDefaultProductOfferInterface $productConcreteDefaultProductOffer,
        ProductOfferStorageCollectionSorterPluginInterface $productOfferStorageCollectionSorterPlugin,
        array $priceProductOfferStorageExpanderPlugins
    ) {
        $this->storageClient = $storageClient;
        $this->merchantProductOfferMapper = $merchantProductOfferMapper;
        $this->merchantStorageClient = $merchantStorageClient;
        $this->utilEncodingService = $utilEncodingService;
        $this->productOfferStorageKeyGenerator = $productOfferStorageKeyGenerator;
        $this->productConcreteDefaultProductOffer = $productConcreteDefaultProductOffer;
        $this->productOfferStorageCollectionSorterPlugin = $productOfferStorageCollectionSorterPlugin;
        $this->priceProductOfferStorageExpanderPlugins = $priceProductOfferStorageExpanderPlugins;
    }

    /**
     * @param \Generated\Shared\Transfer\ProductOfferStorageCriteriaTransfer $productOfferStorageCriteriaTransfer
     *
     * @return \Generated\Shared\Transfer\ProductOfferStorageCollectionTransfer
     */
    public function getProductOffersBySkus(
        ProductOfferStorageCriteriaTransfer $productOfferStorageCriteriaTransfer
    ): ProductOfferStorageCollectionTransfer {
        $productOfferStorageCollectionTransfer = new ProductOfferStorageCollectionTransfer();

        $productConcreteSkus = $productOfferStorageCriteriaTransfer->getProductConcreteSkus();
        if (!$productConcreteSkus) {
            return $productOfferStorageCollectionTransfer;
        }

        $productOfferReferences = $this->getProductOfferReferences($productConcreteSkus);
        if (!$productOfferReferences) {
            return $productOfferStorageCollectionTransfer;
        }

        $productOfferStorageTransfers = $this->getProductOfferStorageByReferences(array_unique(array_filter($productOfferReferences)));
        if (!$productOfferStorageTransfers) {
            return $productOfferStorageCollectionTransfer;
        }

        if ($productOfferStorageCriteriaTransfer->getMerchantReference()) {
            $productOfferStorageTransfers = $this->filterProductOfferStorageTransfersByMerchantReference(
                $productOfferStorageTransfers,
                $productOfferStorageCriteriaTransfer->getMerchantReference()
            );
        }

        $productOfferStorageTransfers = $this->expandProductOffersWithMerchants($productOfferStorageTransfers);
        $productOfferStorageTransfers = $this->expandProductOffersWithPrices($productOfferStorageTransfers);
        $productOfferStorageTransfers = $this->expandProductOffersWithDefaultProductOffer($productOfferStorageTransfers, $productOfferStorageCriteriaTransfer);

        $productOfferStorageCollectionTransfer->setProductOffersStorage(new ArrayObject($productOfferStorageTransfers));

        return $this->productOfferStorageCollectionSorterPlugin->sort($productOfferStorageCollectionTransfer);
    }

    /**
     * @param string $productOfferReference
     *
     * @return \Generated\Shared\Transfer\ProductOfferStorageTransfer|null
     */
    public function findProductOfferStorageByReference(string $productOfferReference): ?ProductOfferStorageTransfer
    {
        $productOfferStorageTransfers = $this->getProductOfferStorageByReferences([$productOfferReference]);

        return $productOfferStorageTransfers[0] ?? null;
    }

    /**
     * @param string[] $productOfferReferences
     *
     * @return \Generated\Shared\Transfer\ProductOfferStorageTransfer[]
     */
    public function getProductOfferStorageByReferences(array $productOfferReferences): array
    {
        $merchantProductOfferKeys = $this->productOfferStorageKeyGenerator->generateMerchantProductOfferKeys($productOfferReferences);
        $productOfferData = $this->storageClient->getMulti($merchantProductOfferKeys);

        $productOfferStorageTransfers = [];
        foreach ($productOfferData as $productOfferDataItem) {
            if (!$productOfferDataItem) {
                continue;
            }

            $decodedMerchantProductOfferStorageData = $this->utilEncodingService->decodeJson($productOfferDataItem, true);

            if (!$decodedMerchantProductOfferStorageData) {
                continue;
            }

            $productOfferStorageTransfers[] = $this->merchantProductOfferMapper->mapMerchantProductOfferStorageDataToProductOfferStorageTransfer(
                $decodedMerchantProductOfferStorageData,
                new ProductOfferStorageTransfer()
            );
        }

        return $productOfferStorageTransfers;
    }

    /**
     * @param string[] $productConcreteSkus
     *
     * @return string[]
     */
    protected function getProductOfferReferences(array $productConcreteSkus): array
    {
        $concreteProductOffersKeys = $this->productOfferStorageKeyGenerator
            ->generateProductConcreteProductOffersKeys($productConcreteSkus);
        $concreteProductOffers = $this->storageClient->getMulti($concreteProductOffersKeys);

        $concreteProductOfferReferences = [];
        foreach ($concreteProductOffers as $storageKey => $concreteProductOffer) {
            if (!$concreteProductOffer) {
                continue;
            }

            $decodedConcreteProductOffer = $this->utilEncodingService->decodeJson($concreteProductOffer, true);

            if (!$decodedConcreteProductOffer) {
                continue;
            }

            unset($decodedConcreteProductOffer['_timestamp']);

            $concreteProductOfferReferences = array_merge($concreteProductOfferReferences, $decodedConcreteProductOffer);
        }

        return $concreteProductOfferReferences;
    }

    /**
     * @param \Generated\Shared\Transfer\ProductOfferStorageTransfer[] $productOfferStorageTransfers
     * @param string $merchantReference
     *
     * @return \Generated\Shared\Transfer\ProductOfferStorageTransfer[]
     */
    protected function filterProductOfferStorageTransfersByMerchantReference(
        array $productOfferStorageTransfers,
        string $merchantReference
    ): array {
        $filteredProductOfferStorageTransfers = [];
        foreach ($productOfferStorageTransfers as $productOfferStorageTransfer) {
            if ($productOfferStorageTransfer->getMerchantReference() !== $merchantReference) {
                continue;
            }

            $filteredProductOfferStorageTransfers[] = $productOfferStorageTransfer;
        }

        return $filteredProductOfferStorageTransfers;
    }

    /**
     * @param \Generated\Shared\Transfer\ProductOfferStorageTransfer[] $productOfferStorageTransfers
     *
     * @return \Generated\Shared\Transfer\ProductOfferStorageTransfer[]
     */
    protected function expandProductOffersWithMerchants(array $productOfferStorageTransfers): array
    {
        $merchantIds = $this->getMerchantIds($productOfferStorageTransfers);
        $merchantStorageTransfers = $this->merchantStorageClient->get(array_unique($merchantIds));
        $merchantStorageTransfers = $this->indexMerchantStorageTransfersByIdMerchant($merchantStorageTransfers);

        foreach ($productOfferStorageTransfers as $key => $productOfferStorageTransfer) {
            $idMerchant = $productOfferStorageTransfer->getIdMerchant();

            if (!isset($merchantStorageTransfers[$idMerchant])) {
                unset($productOfferStorageTransfers[$key]);

                continue;
            }

            $productOfferStorageTransfer->setMerchantStorage($merchantStorageTransfers[$idMerchant]);
        }

        return $productOfferStorageTransfers;
    }

    /**
     * @param \Generated\Shared\Transfer\ProductOfferStorageTransfer[] $productOfferStorageTransfers
     *
     * @return \Generated\Shared\Transfer\ProductOfferStorageTransfer[]
     */
    protected function expandProductOffersWithPrices(array $productOfferStorageTransfers): array
    {
        foreach ($productOfferStorageTransfers as $key => $productOfferStorageTransfer) {
            foreach ($this->priceProductOfferStorageExpanderPlugins as $priceProductOfferStorageExpanderPlugin) {
                $productOfferStorageTransfers[$key] = $priceProductOfferStorageExpanderPlugin->expand($productOfferStorageTransfer);
            }
        }

        return $productOfferStorageTransfers;
    }

    /**
     * @param \Generated\Shared\Transfer\ProductOfferStorageTransfer[] $productOfferStorageTransfers
     * @param \Generated\Shared\Transfer\ProductOfferStorageCriteriaTransfer $productOfferStorageCriteriaTransfer
     *
     * @return \Generated\Shared\Transfer\ProductOfferStorageTransfer[]
     */
    protected function expandProductOffersWithDefaultProductOffer(
        array $productOfferStorageTransfers,
        ProductOfferStorageCriteriaTransfer $productOfferStorageCriteriaTransfer
    ): array {
        $defaultProductOffers = $this->productConcreteDefaultProductOffer->getProductOfferReferences(
            $productOfferStorageTransfers,
            $productOfferStorageCriteriaTransfer
        );

        foreach ($productOfferStorageTransfers as $productOfferStorageTransfer) {
            if (
                !isset($defaultProductOffers[$productOfferStorageTransfer->getProductConcreteSku()])
                || $productOfferStorageTransfer->getProductOfferReference() !== $defaultProductOffers[$productOfferStorageTransfer->getProductConcreteSku()]
            ) {
                $productOfferStorageTransfer->setIsDefault(false);

                continue;
            }

            $productOfferStorageTransfer->setIsDefault(true);
        }

        return $productOfferStorageTransfers;
    }

    /**
     * @param \Generated\Shared\Transfer\ProductOfferStorageTransfer[] $productOfferStorageTransfers
     *
     * @return int[]
     */
    protected function getMerchantIds(array $productOfferStorageTransfers): array
    {
        $merchantIds = [];
        foreach ($productOfferStorageTransfers as $productOfferStorageTransfer) {
            $merchantIds[] = $productOfferStorageTransfer->getIdMerchant();
        }

        return $merchantIds;
    }

    /**
     * @param \Generated\Shared\Transfer\MerchantStorageTransfer[] $merchantStorageTransfers
     *
     * @return \Generated\Shared\Transfer\MerchantStorageTransfer[]
     */
    protected function indexMerchantStorageTransfersByIdMerchant(array $merchantStorageTransfers): array
    {
        $indexedMerchantStorageTransfers = [];
        foreach ($merchantStorageTransfers as $merchantStorageTransfer) {
            $indexedMerchantStorageTransfers[$merchantStorageTransfer->getIdMerchant()] = $merchantStorageTransfer;
        }

        return $indexedMerchantStorageTransfers;
    }
}
