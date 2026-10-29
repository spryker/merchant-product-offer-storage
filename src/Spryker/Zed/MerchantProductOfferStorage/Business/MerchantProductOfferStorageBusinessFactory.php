<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\MerchantProductOfferStorage\Business;

use Spryker\Shared\MerchantProductOfferStorage\MerchantProductOfferStorageConfig as SharedMerchantProductOfferStorageConfig;
use Spryker\Zed\Event\Business\EventFacadeInterface;
use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;
use Spryker\Zed\MerchantProductOfferStorage\Business\Filter\MerchantProductOfferServiceStorageFilter;
use Spryker\Zed\MerchantProductOfferStorage\Business\Filter\MerchantProductOfferServiceStorageFilterInterface;
use Spryker\Zed\MerchantProductOfferStorage\Business\Filter\MerchantProductOfferShipmentTypeStorageFilter;
use Spryker\Zed\MerchantProductOfferStorage\Business\Filter\MerchantProductOfferShipmentTypeStorageFilterInterface;
use Spryker\Zed\MerchantProductOfferStorage\Business\Publisher\ProductOfferRangeStoragePublisher;
use Spryker\Zed\MerchantProductOfferStorage\Business\Publisher\ProductOfferRangeStoragePublisherInterface;
use Spryker\Zed\MerchantProductOfferStorage\Business\Reader\ProductOfferRangeReader;
use Spryker\Zed\MerchantProductOfferStorage\Business\Reader\ProductOfferRangeReaderInterface;
use Spryker\Zed\MerchantProductOfferStorage\Business\Trigger\ProductOfferRangeEventTrigger;
use Spryker\Zed\MerchantProductOfferStorage\Business\Trigger\ProductOfferRangeEventTriggerInterface;
use Spryker\Zed\MerchantProductOfferStorage\Business\Writer\ProductConcreteOffersStorageWriter;
use Spryker\Zed\MerchantProductOfferStorage\Business\Writer\ProductConcreteOffersStorageWriterInterface;
use Spryker\Zed\MerchantProductOfferStorage\Business\Writer\ProductConcreteProductOffersRangeStorageWriter;
use Spryker\Zed\MerchantProductOfferStorage\Business\Writer\ProductOfferRangeStorageWriter;
use Spryker\Zed\MerchantProductOfferStorage\Business\Writer\ProductOfferRangeStorageWriterInterface;
use Spryker\Zed\MerchantProductOfferStorage\Business\Writer\ProductOfferStorageWriter;
use Spryker\Zed\MerchantProductOfferStorage\Business\Writer\ProductOfferStorageWriterInterface;
use Spryker\Zed\MerchantProductOfferStorage\Dependency\Facade\MerchantProductOfferStorageToEventBehaviorFacadeInterface;
use Spryker\Zed\MerchantProductOfferStorage\Dependency\Facade\MerchantProductOfferStorageToMerchantFacadeInterface;
use Spryker\Zed\MerchantProductOfferStorage\Dependency\Facade\MerchantProductOfferStorageToProductOfferStorageFacadeInterface;
use Spryker\Zed\MerchantProductOfferStorage\MerchantProductOfferStorageDependencyProvider;

/**
 * @method \Spryker\Zed\MerchantProductOfferStorage\Persistence\MerchantProductOfferStorageRepositoryInterface getRepository()
 * @method \Spryker\Zed\MerchantProductOfferStorage\MerchantProductOfferStorageConfig getConfig()
 */
class MerchantProductOfferStorageBusinessFactory extends AbstractBusinessFactory
{
    public function createProductConcreteProductOffersStorageWriter(): ProductConcreteOffersStorageWriterInterface
    {
        return new ProductConcreteOffersStorageWriter(
            $this->getEventBehaviorFacade(),
            $this->getRepository(),
            $this->getProductOfferStorageFacade(),
        );
    }

    public function createProductOfferStorageWriter(): ProductOfferStorageWriterInterface
    {
        return new ProductOfferStorageWriter(
            $this->getEventBehaviorFacade(),
            $this->getRepository(),
            $this->getProductOfferStorageFacade(),
            $this->getConfig(),
        );
    }

    public function createProductConcreteProductOffersStoragePublisher(): ProductOfferRangeStoragePublisherInterface
    {
        return new ProductOfferRangeStoragePublisher(
            $this->createProductOfferRangeEventTrigger(),
            $this->createProductConcreteProductOffersRangeStorageWriter(),
            SharedMerchantProductOfferStorageConfig::PRODUCT_CONCRETE_PRODUCT_OFFERS_RANGE_PUBLISH,
        );
    }

    public function createProductOfferStoragePublisher(): ProductOfferRangeStoragePublisherInterface
    {
        return new ProductOfferRangeStoragePublisher(
            $this->createProductOfferRangeEventTrigger(),
            $this->createProductOfferRangeStorageWriter(),
            SharedMerchantProductOfferStorageConfig::PRODUCT_OFFER_RANGE_PUBLISH,
        );
    }

    public function createProductOfferRangeEventTrigger(): ProductOfferRangeEventTriggerInterface
    {
        return new ProductOfferRangeEventTrigger(
            $this->getEventBehaviorFacade(),
            $this->getRepository(),
            $this->getEventFacade(),
            $this->getConfig(),
        );
    }

    public function createProductOfferRangeStorageWriter(): ProductOfferRangeStorageWriterInterface
    {
        return new ProductOfferRangeStorageWriter(
            $this->createProductOfferRangeReader(),
            $this->getProductOfferStorageFacade(),
            $this->getConfig(),
        );
    }

    public function createProductConcreteProductOffersRangeStorageWriter(): ProductOfferRangeStorageWriterInterface
    {
        return new ProductConcreteProductOffersRangeStorageWriter(
            $this->createProductOfferRangeReader(),
            $this->getProductOfferStorageFacade(),
        );
    }

    public function createProductOfferRangeReader(): ProductOfferRangeReaderInterface
    {
        return new ProductOfferRangeReader($this->getRepository());
    }

    public function createMerchantProductOfferServiceStorageFilter(): MerchantProductOfferServiceStorageFilterInterface
    {
        return new MerchantProductOfferServiceStorageFilter(
            $this->getMerchantFacade(),
        );
    }

    public function createMerchantProductOfferShipmentTypeStorageFilter(): MerchantProductOfferShipmentTypeStorageFilterInterface
    {
        return new MerchantProductOfferShipmentTypeStorageFilter($this->getMerchantFacade());
    }

    public function getEventBehaviorFacade(): MerchantProductOfferStorageToEventBehaviorFacadeInterface
    {
        return $this->getProvidedDependency(MerchantProductOfferStorageDependencyProvider::FACADE_EVENT_BEHAVIOR);
    }

    public function getProductOfferStorageFacade(): MerchantProductOfferStorageToProductOfferStorageFacadeInterface
    {
        return $this->getProvidedDependency(MerchantProductOfferStorageDependencyProvider::FACADE_PRODUCT_OFFER_STORAGE);
    }

    public function getEventFacade(): EventFacadeInterface
    {
        return $this->getProvidedDependency(MerchantProductOfferStorageDependencyProvider::FACADE_EVENT);
    }

    public function getMerchantFacade(): MerchantProductOfferStorageToMerchantFacadeInterface
    {
        return $this->getProvidedDependency(MerchantProductOfferStorageDependencyProvider::FACADE_MERCHANT);
    }
}
