<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\MerchantProductOfferStorage\Communication\Plugin\Event\Subscriber;

use Spryker\Zed\Event\Dependency\EventCollectionInterface;
use Spryker\Zed\Event\Dependency\Plugin\EventSubscriberInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\MerchantProductOffer\Dependency\MerchantProductOfferEvents;
use Spryker\Zed\MerchantProductOffer\Dependency\MerchantProductOfferStoreEvents;
use Spryker\Zed\MerchantProductOfferStorage\Communication\Plugin\Event\Listener\ProductConcreteOffersStoragePublishListener;
use Spryker\Zed\MerchantProductOfferStorage\Communication\Plugin\Event\Listener\ProductConcreteOffersStorageUnpublishListener;
use Spryker\Zed\MerchantProductOfferStorage\Communication\Plugin\Event\Listener\ProductOfferStoragePublishListener;
use Spryker\Zed\MerchantProductOfferStorage\Communication\Plugin\Event\Listener\ProductOfferStorageUnpublishListener;

/**
 * @method \Spryker\Zed\MerchantProductOfferStorage\MerchantProductOfferStorageConfig getConfig()
 * @method \Spryker\Zed\MerchantProductOfferStorage\Business\MerchantProductOfferStorageFacadeInterface getFacade()
 * @method \Spryker\Zed\MerchantProductOfferStorage\Communication\MerchantProductOfferStorageCommunicationFactory getFactory()
 */
class MerchantProductOfferStorageEventSubscriber extends AbstractPlugin implements EventSubscriberInterface
{
    /**
     * @param \Spryker\Zed\Event\Dependency\EventCollectionInterface $eventCollection
     *
     * @return \Spryker\Zed\Event\Dependency\EventCollectionInterface
     */
    public function getSubscribedEvents(EventCollectionInterface $eventCollection): EventCollectionInterface
    {
        $eventCollection->addListenerQueued(MerchantProductOfferEvents::MERCHANT_PRODUCT_OFFER_PUBLISH, new ProductOfferStoragePublishListener());
        $eventCollection->addListenerQueued(MerchantProductOfferEvents::ENTITY_SPY_PRODUCT_OFFER_CREATE, new ProductOfferStoragePublishListener());
        $eventCollection->addListenerQueued(MerchantProductOfferEvents::ENTITY_SPY_PRODUCT_OFFER_UPDATE, new ProductOfferStoragePublishListener());
        $eventCollection->addListenerQueued(MerchantProductOfferStoreEvents::MERCHANT_PRODUCT_OFFER_STORE_KEY_PUBLISH, new ProductOfferStoragePublishListener());
        $eventCollection->addListenerQueued(MerchantProductOfferStoreEvents::ENTITY_SPY_PRODUCT_OFFER_STORE_CREATE, new ProductOfferStoragePublishListener());
        $eventCollection->addListenerQueued(MerchantProductOfferStoreEvents::ENTITY_SPY_PRODUCT_OFFER_STORE_UPDATE, new ProductOfferStoragePublishListener());

        $eventCollection->addListenerQueued(MerchantProductOfferEvents::MERCHANT_PRODUCT_OFFER_UNPUBLISH, new ProductOfferStorageUnpublishListener());
        $eventCollection->addListenerQueued(MerchantProductOfferEvents::ENTITY_SPY_PRODUCT_OFFER_DELETE, new ProductOfferStorageUnpublishListener());
        $eventCollection->addListenerQueued(MerchantProductOfferStoreEvents::MERCHANT_PRODUCT_OFFER_STORE_KEY_UNPUBLISH, new ProductOfferStorageUnpublishListener());
        $eventCollection->addListenerQueued(MerchantProductOfferStoreEvents::ENTITY_SPY_PRODUCT_OFFER_STORE_DELETE, new ProductOfferStorageUnpublishListener());

        $eventCollection->addListenerQueued(MerchantProductOfferEvents::MERCHANT_PRODUCT_OFFER_PUBLISH, new ProductConcreteOffersStoragePublishListener());
        $eventCollection->addListenerQueued(MerchantProductOfferEvents::ENTITY_SPY_PRODUCT_OFFER_CREATE, new ProductConcreteOffersStoragePublishListener());
        $eventCollection->addListenerQueued(MerchantProductOfferEvents::ENTITY_SPY_PRODUCT_OFFER_UPDATE, new ProductConcreteOffersStoragePublishListener());
        $eventCollection->addListenerQueued(MerchantProductOfferStoreEvents::MERCHANT_PRODUCT_OFFER_STORE_KEY_PUBLISH, new ProductConcreteOffersStoragePublishListener());
        $eventCollection->addListenerQueued(MerchantProductOfferStoreEvents::ENTITY_SPY_PRODUCT_OFFER_STORE_CREATE, new ProductConcreteOffersStoragePublishListener());
        $eventCollection->addListenerQueued(MerchantProductOfferStoreEvents::ENTITY_SPY_PRODUCT_OFFER_STORE_UPDATE, new ProductConcreteOffersStoragePublishListener());

        $eventCollection->addListenerQueued(MerchantProductOfferEvents::MERCHANT_PRODUCT_OFFER_UNPUBLISH, new ProductConcreteOffersStorageUnpublishListener());
        $eventCollection->addListenerQueued(MerchantProductOfferEvents::ENTITY_SPY_PRODUCT_OFFER_DELETE, new ProductConcreteOffersStorageUnpublishListener());
        $eventCollection->addListenerQueued(MerchantProductOfferEvents::MERCHANT_PRODUCT_OFFER_UNPUBLISH, new ProductConcreteOffersStoragePublishListener());
        $eventCollection->addListenerQueued(MerchantProductOfferEvents::ENTITY_SPY_PRODUCT_OFFER_DELETE, new ProductConcreteOffersStoragePublishListener());
        $eventCollection->addListenerQueued(MerchantProductOfferStoreEvents::MERCHANT_PRODUCT_OFFER_STORE_KEY_UNPUBLISH, new ProductConcreteOffersStorageUnpublishListener());
        $eventCollection->addListenerQueued(MerchantProductOfferStoreEvents::ENTITY_SPY_PRODUCT_OFFER_STORE_DELETE, new ProductConcreteOffersStorageUnpublishListener());
        $eventCollection->addListenerQueued(MerchantProductOfferStoreEvents::MERCHANT_PRODUCT_OFFER_STORE_KEY_UNPUBLISH, new ProductConcreteOffersStoragePublishListener());
        $eventCollection->addListenerQueued(MerchantProductOfferStoreEvents::ENTITY_SPY_PRODUCT_OFFER_STORE_DELETE, new ProductConcreteOffersStoragePublishListener());

        return $eventCollection;
    }
}
