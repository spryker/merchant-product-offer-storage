<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\MerchantProductOfferStorage\Business\Trigger;

use Generated\Shared\Transfer\EventEntityTransfer;
use Spryker\Shared\MerchantProductOfferStorage\MerchantProductOfferStorageConfig as SharedMerchantProductOfferStorageConfig;
use Spryker\Zed\Event\Business\EventFacadeInterface;
use Spryker\Zed\MerchantProductOfferStorage\Dependency\Facade\MerchantProductOfferStorageToEventBehaviorFacadeInterface;
use Spryker\Zed\MerchantProductOfferStorage\MerchantProductOfferStorageConfig;
use Spryker\Zed\MerchantProductOfferStorage\Persistence\MerchantProductOfferStorageRepositoryInterface;

class ProductOfferRangeEventTrigger implements ProductOfferRangeEventTriggerInterface
{
    public function __construct(
        protected MerchantProductOfferStorageToEventBehaviorFacadeInterface $eventBehaviorFacade,
        protected MerchantProductOfferStorageRepositoryInterface $merchantProductOfferStorageRepository,
        protected EventFacadeInterface $eventFacade,
        protected MerchantProductOfferStorageConfig $merchantProductOfferStorageConfig
    ) {
    }

    /**
     * @param array<\Generated\Shared\Transfer\EventEntityTransfer> $eventEntityTransfers
     */
    public function triggerByMerchantEvents(array $eventEntityTransfers, string $rangeEventName): void
    {
        $merchantIds = $this->eventBehaviorFacade->getEventTransferIds(
            $this->filterProductOfferStorageRelevantMerchantEvents($eventEntityTransfers),
        );

        if (!$merchantIds) {
            return;
        }

        $rangeSize = max(1, $this->merchantProductOfferStorageConfig->getMerchantProductOfferRangeSize());
        $readBatchSize = max(1, intdiv($this->merchantProductOfferStorageConfig->getMerchantProductOfferRangeReadBatchSize(), $rangeSize)) * $rangeSize;

        foreach ($this->merchantProductOfferStorageRepository->getMerchantReferencesByMerchantIds($merchantIds) as $merchantReference) {
            $this->triggerByMerchantReference($merchantReference, $rangeEventName, $rangeSize, $readBatchSize);
        }
    }

    protected function triggerByMerchantReference(string $merchantReference, string $rangeEventName, int $rangeSize, int $readBatchSize): void
    {
        $productOfferIdIterator = $this->merchantProductOfferStorageRepository->iterateProductOfferIdsByMerchantReference($merchantReference, $readBatchSize);

        foreach ($productOfferIdIterator as $productOfferIds) {
            $this->eventFacade->triggerBulk(
                $rangeEventName,
                $this->createRangeEventEntityTransfers($merchantReference, $productOfferIds, $rangeSize),
            );
        }
    }

    /**
     * @param list<int> $productOfferIds
     *
     * @return list<\Generated\Shared\Transfer\EventEntityTransfer>
     */
    protected function createRangeEventEntityTransfers(string $merchantReference, array $productOfferIds, int $rangeSize): array
    {
        $rangeEventEntityTransfers = [];

        foreach (array_chunk($productOfferIds, max(1, $rangeSize)) as $rangeProductOfferIds) {
            $rangeEventEntityTransfers[] = (new EventEntityTransfer())->setAdditionalValues([
                SharedMerchantProductOfferStorageConfig::RANGE_MERCHANT_REFERENCE => $merchantReference,
                SharedMerchantProductOfferStorageConfig::RANGE_FROM_ID_PRODUCT_OFFER => reset($rangeProductOfferIds),
                SharedMerchantProductOfferStorageConfig::RANGE_TO_ID_PRODUCT_OFFER => end($rangeProductOfferIds),
            ]);
        }

        return $rangeEventEntityTransfers;
    }

    /**
     * @param array<\Generated\Shared\Transfer\EventEntityTransfer> $eventEntityTransfers
     *
     * @return list<\Generated\Shared\Transfer\EventEntityTransfer>
     */
    protected function filterProductOfferStorageRelevantMerchantEvents(array $eventEntityTransfers): array
    {
        $relevantMerchantColumns = $this->merchantProductOfferStorageConfig->getProductOfferStorageRelevantMerchantColumns();

        // An event without modified columns stands for a change of the whole merchant, for example a republish.
        return array_values(array_filter(
            $eventEntityTransfers,
            fn (EventEntityTransfer $eventEntityTransfer): bool => !$eventEntityTransfer->getModifiedColumns()
                || array_intersect($eventEntityTransfer->getModifiedColumns(), $relevantMerchantColumns) !== [],
        ));
    }
}
