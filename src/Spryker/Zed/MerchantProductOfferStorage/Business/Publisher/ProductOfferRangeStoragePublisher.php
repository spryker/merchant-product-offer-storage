<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\MerchantProductOfferStorage\Business\Publisher;

use Spryker\Zed\MerchantProductOfferStorage\Business\Trigger\ProductOfferRangeEventTriggerInterface;
use Spryker\Zed\MerchantProductOfferStorage\Business\Writer\ProductOfferRangeStorageWriterInterface;

class ProductOfferRangeStoragePublisher implements ProductOfferRangeStoragePublisherInterface
{
    public function __construct(
        protected ProductOfferRangeEventTriggerInterface $productOfferRangeEventTrigger,
        protected ProductOfferRangeStorageWriterInterface $productOfferRangeStorageWriter,
        protected string $rangeEventName
    ) {
    }

    /**
     * @param array<\Generated\Shared\Transfer\EventEntityTransfer> $eventEntityTransfers
     */
    public function publish(array $eventEntityTransfers, string $eventName): void
    {
        if ($eventName === $this->rangeEventName) {
            $this->productOfferRangeStorageWriter->writeCollectionByRangeEvents($eventEntityTransfers);

            return;
        }

        $this->productOfferRangeEventTrigger->triggerByMerchantEvents($eventEntityTransfers, $this->rangeEventName);
    }
}
