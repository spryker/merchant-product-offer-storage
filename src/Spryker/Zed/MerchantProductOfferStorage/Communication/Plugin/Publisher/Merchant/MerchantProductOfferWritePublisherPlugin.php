<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\MerchantProductOfferStorage\Communication\Plugin\Publisher\Merchant;

use Spryker\Shared\MerchantProductOfferStorage\MerchantProductOfferStorageConfig;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\Merchant\Dependency\MerchantEvents;
use Spryker\Zed\PublisherExtension\Dependency\Plugin\PublisherPluginInterface;

/**
 * @method \Spryker\Zed\MerchantProductOfferStorage\MerchantProductOfferStorageConfig getConfig()
 * @method \Spryker\Zed\MerchantProductOfferStorage\Business\MerchantProductOfferStorageFacadeInterface getFacade()
 * @method \Spryker\Zed\MerchantProductOfferStorage\Communication\MerchantProductOfferStorageCommunicationFactory getFactory()
 * @method \Spryker\Zed\MerchantProductOfferStorage\Business\MerchantProductOfferStorageBusinessFactory getBusinessFactory()
 */
class MerchantProductOfferWritePublisherPlugin extends AbstractPlugin implements PublisherPluginInterface
{
    /**
     * {@inheritDoc}
     * - For `MerchantProductOfferStorage.product_offer_range.publish` events: writes the product offer storage of the product offers of the merchant in the product offer ID range of each event.
     * - For merchant events: skips the events whose modified columns contain none of {@link \Spryker\Zed\MerchantProductOfferStorage\MerchantProductOfferStorageConfig::getProductOfferStorageRelevantMerchantColumns()}.
     * - For merchant events: does not skip the events without modified columns.
     * - For merchant events: splits the product offer IDs of each merchant into ranges of {@link \Spryker\Zed\MerchantProductOfferStorage\MerchantProductOfferStorageConfig::getMerchantProductOfferRangeSize()} product offers.
     * - For merchant events: triggers one `MerchantProductOfferStorage.product_offer_range.publish` event per range.
     *
     * @api
     *
     * @param array<\Generated\Shared\Transfer\EventEntityTransfer> $eventEntityTransfers
     * @param string $eventName
     *
     * @return void
     */
    public function handleBulk(array $eventEntityTransfers, $eventName)
    {
        $this->getBusinessFactory()
            ->createProductOfferStoragePublisher()
            ->publish($eventEntityTransfers, $eventName);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return array<string>
     */
    public function getSubscribedEvents(): array
    {
        return [
            MerchantEvents::ENTITY_SPY_MERCHANT_UPDATE,
            MerchantEvents::MERCHANT_PUBLISH,
            MerchantProductOfferStorageConfig::PRODUCT_OFFER_RANGE_PUBLISH,
        ];
    }
}
