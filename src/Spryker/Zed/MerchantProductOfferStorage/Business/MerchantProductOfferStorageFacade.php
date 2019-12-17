<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\MerchantProductOfferStorage\Business;

use Spryker\Zed\Kernel\Business\AbstractFacade;

/**
 * @method \Spryker\Zed\MerchantProductOfferStorage\Business\MerchantProductOfferStorageBusinessFactory getFactory()
 * @method \Spryker\Zed\MerchantProductOfferStorage\Persistence\MerchantProductOfferStorageRepositoryInterface getRepository()
 * @method \Spryker\Zed\MerchantProductOfferStorage\Persistence\MerchantProductOfferStorageEntityManagerInterface getEntityManager()
 */
class MerchantProductOfferStorageFacade extends AbstractFacade implements MerchantProductOfferStorageFacadeInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param string[] $productSkus
     *
     * @return void
     */
    public function publishProductConcreteProductOffersStorage(array $productSkus): void
    {
        $this->getFactory()
            ->createProductConcreteProductOffersStorageWriter()
            ->writeProductConcreteProductOffersStorageCollectionByProductSkus($productSkus);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param string[] $productSkus
     *
     * @return void
     */
    public function unpublishProductConcreteProductOffersStorage(array $productSkus): void
    {
        $this->getFactory()
            ->createProductConcreteProductOffersStorageDeleter()
            ->deleteProductConcreteProductOffersStorageCollectionByProductSkus($productSkus);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param string[] $productOfferReferences
     *
     * @return void
     */
    public function publishProductOfferStorage(array $productOfferReferences): void
    {
        $this->getFactory()
            ->createProductOfferStorageWriter()
            ->writeProductOfferStorageCollectionByProductOfferReferences($productOfferReferences);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param string[] $productOfferReferences
     *
     * @return void
     */
    public function unpublishProductOfferStorage(array $productOfferReferences): void
    {
        $this->getFactory()
            ->createProductOfferStorageDeleter()
            ->deleteProductOfferStorageCollectionByProductOfferReferences($productOfferReferences);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\EventEntityTransfer[] $eventTransfers
     *
     * @return void
     */
    public function writeProductConcreteProductOffersStorageCollectionByProductSkuEvents(array $eventTransfers): void
    {
        $this->getFactory()
            ->createProductConcreteProductOffersStorageWriter()
            ->writeProductConcreteProductOffersStorageCollectionByProductSkuEvents($eventTransfers);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\EventEntityTransfer[] $eventTransfers
     *
     * @return void
     */
    public function deleteProductConcreteProductOffersStorageCollectionByProductSkuEvents(array $eventTransfers): void
    {
        $this->getFactory()
            ->createProductConcreteProductOffersStorageDeleter()
            ->deleteProductConcreteProductOffersStorageCollectionByProductSkuEvents($eventTransfers);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\EventEntityTransfer[] $eventTransfers
     *
     * @return void
     */
    public function writeProductOfferStorageCollectionByProductOfferReferenceEvents(array $eventTransfers): void
    {
        $this->getFactory()
            ->createProductOfferStorageWriter()
            ->writeProductOfferStorageCollectionByProductOfferReferenceEvents($eventTransfers);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\EventEntityTransfer[] $eventTransfers
     *
     * @return void
     */
    public function deleteProductOfferStorageCollectionByProductOfferReferenceEvents(array $eventTransfers): void
    {
        $this->getFactory()
            ->createProductOfferStorageDeleter()
            ->deleteProductOfferStorageCollectionByProductOfferReferenceEvents($eventTransfers);
    }
}
