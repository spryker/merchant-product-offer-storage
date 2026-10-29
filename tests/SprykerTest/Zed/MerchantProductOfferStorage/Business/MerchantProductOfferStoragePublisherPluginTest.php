<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\MerchantProductOfferStorage\Business;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\EventEntityTransfer;
use Generated\Shared\Transfer\EventQueueSendMessageBodyTransfer;
use Generated\Shared\Transfer\MerchantTransfer;
use Generated\Shared\Transfer\ProductOfferTransfer;
use Generated\Shared\Transfer\QueueReceiveMessageTransfer;
use Generated\Shared\Transfer\QueueSendMessageTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Orm\Zed\Merchant\Persistence\Map\SpyMerchantStoreTableMap;
use Orm\Zed\Merchant\Persistence\Map\SpyMerchantTableMap;
use Spryker\Client\Kernel\Container;
use Spryker\Client\Queue\QueueDependencyProvider;
use Spryker\Shared\MerchantProductOfferStorage\MerchantProductOfferStorageConfig;
use Spryker\Zed\Event\Business\EventFacadeInterface;
use Spryker\Zed\Merchant\MerchantConfig;
use Spryker\Zed\MerchantProductOfferStorage\Business\MerchantProductOfferStorageBusinessFactory;
use Spryker\Zed\MerchantProductOfferStorage\Business\Trigger\ProductOfferRangeEventTrigger;
use Spryker\Zed\MerchantProductOfferStorage\Communication\Plugin\Publisher\Merchant\MerchantProductOfferWritePublisherPlugin;
use Spryker\Zed\MerchantProductOfferStorage\Communication\Plugin\Publisher\ProductConcreteProductOffer\MerchantProductConcreteProductOfferWritePublisherPlugin;
use Spryker\Zed\MerchantProductOfferStorage\MerchantProductOfferStorageConfig as ZedMerchantProductOfferStorageConfig;
use Spryker\Zed\MerchantProductOfferStorage\MerchantProductOfferStorageDependencyProvider;
use Spryker\Zed\MerchantProductOfferStorage\Persistence\MerchantProductOfferStorageRepository;
use SprykerTest\Zed\MerchantProductOfferStorage\MerchantProductOfferStorageTester;
use SprykerTest\Zed\Testify\Helper\Business\BusinessHelper;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group MerchantProductOfferStorage
 * @group Business
 * @group MerchantProductOfferStoragePublisherPluginTest
 * Add your own group annotations below this line
 */
class MerchantProductOfferStoragePublisherPluginTest extends Unit
{
    protected MerchantProductOfferStorageTester $tester;

    /**
     * @var array<string, list<\Generated\Shared\Transfer\EventEntityTransfer>>
     */
    protected array $triggeredEventEntityTransfers = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->tester->setDependency(QueueDependencyProvider::QUEUE_ADAPTERS, function (Container $container) {
            return [
                $this->tester->getLocator()->rabbitMq()->client()->createQueueAdapter(),
                $container->getLocator()->symfonyMessenger()->client()->createQueueAdapter(),
            ];
        });
        $this->tester->addDependencies();

        $this->triggeredEventEntityTransfers = [];
        $eventFacadeMock = $this->createMock(EventFacadeInterface::class);
        $eventFacadeMock->method('triggerBulk')->willReturnCallback(function (string $eventName, array $eventEntityTransfers): void {
            $this->triggeredEventEntityTransfers[$eventName] = array_merge(
                $this->triggeredEventEntityTransfers[$eventName] ?? [],
                $eventEntityTransfers,
            );
        });
        $this->tester->setDependency(
            MerchantProductOfferStorageDependencyProvider::FACADE_EVENT,
            $eventFacadeMock,
            MerchantProductOfferStorageBusinessFactory::class,
        );
    }

    /**
     * @dataProvider getRangeSizeAndReadBatchSizeDataProvider
     */
    public function testGivenMerchantEventsWithoutModifiedColumnsWhenRangeEventsArePublishedThenEveryOfferAndSkuOfTheMerchantsIsWritten(
        int $rangeSize,
        int $readBatchSize
    ): void {
        // Arrange
        $this->tester->clearProductOfferData();
        $firstMerchantTransfer = $this->tester->haveMerchant([MerchantTransfer::IS_ACTIVE => true]);
        $secondMerchantTransfer = $this->tester->haveMerchant([MerchantTransfer::IS_ACTIVE => true]);
        $sharedSku = $this->tester->haveProduct()->getSkuOrFail();
        $productOfferTransfers = [
            $this->haveProductOffer($firstMerchantTransfer, $sharedSku),
            $this->haveProductOffer($secondMerchantTransfer, $this->tester->haveProduct()->getSkuOrFail()),
            $this->haveProductOffer($firstMerchantTransfer, $this->tester->haveProduct()->getSkuOrFail()),
            $this->haveProductOffer($firstMerchantTransfer, $this->tester->haveProduct()->getSkuOrFail()),
            $this->haveProductOffer($secondMerchantTransfer, $sharedSku),
        ];
        $eventEntityTransfers = [
            (new EventEntityTransfer())->setId($firstMerchantTransfer->getIdMerchantOrFail()),
            (new EventEntityTransfer())->setId($secondMerchantTransfer->getIdMerchantOrFail()),
        ];
        $productOfferRangeEventTrigger = $this->createProductOfferRangeEventTrigger($rangeSize, $readBatchSize);

        // Act
        $productOfferRangeEventTrigger->triggerByMerchantEvents($eventEntityTransfers, MerchantProductOfferStorageConfig::PRODUCT_OFFER_RANGE_PUBLISH);
        $productOfferRangeEventTrigger->triggerByMerchantEvents($eventEntityTransfers, MerchantProductOfferStorageConfig::PRODUCT_CONCRETE_PRODUCT_OFFERS_RANGE_PUBLISH);
        $this->publishTriggeredRangeEvents();

        // Assert
        foreach ($productOfferTransfers as $productOfferTransfer) {
            $this->assertCount(1, $this->tester->getProductOfferEntities($productOfferTransfer->getProductOfferReferenceOrFail()));
            $this->assertCount(1, $this->tester->getProductConcreteProductOffersEntities($productOfferTransfer->getConcreteSkuOrFail()));
        }
    }

    /**
     * @return array<string, array<int>>
     */
    public function getRangeSizeAndReadBatchSizeDataProvider(): array
    {
        return [
            'range size 2, read batch size 3' => [2, 3],
            'range size 2, read batch size 1' => [2, 1],
            'range size 0, read batch size 0' => [0, 0],
            'default range size and read batch size' => [
                (new ZedMerchantProductOfferStorageConfig())->getMerchantProductOfferRangeSize(),
                (new ZedMerchantProductOfferStorageConfig())->getMerchantProductOfferRangeReadBatchSize(),
            ],
        ];
    }

    public function testGivenRangeEventsOfOneMerchantInOneQueueChunkWhenEventQueueConsumerProcessesThemThenEveryRangeIsWritten(): void
    {
        // Arrange
        $this->tester->clearProductOfferData();
        $merchantTransfer = $this->tester->haveMerchant([MerchantTransfer::IS_ACTIVE => true]);
        $firstProductOfferTransfer = $this->haveProductOffer($merchantTransfer, $this->tester->haveProduct()->getSkuOrFail());
        $secondProductOfferTransfer = $this->haveProductOffer($merchantTransfer, $this->tester->haveProduct()->getSkuOrFail());
        $eventEntityTransfers = [(new EventEntityTransfer())->setId($merchantTransfer->getIdMerchantOrFail())];
        $productOfferRangeEventTrigger = $this->createProductOfferRangeEventTrigger(1, 1);
        $productOfferRangeEventTrigger->triggerByMerchantEvents($eventEntityTransfers, MerchantProductOfferStorageConfig::PRODUCT_OFFER_RANGE_PUBLISH);
        $productOfferRangeEventTrigger->triggerByMerchantEvents($eventEntityTransfers, MerchantProductOfferStorageConfig::PRODUCT_CONCRETE_PRODUCT_OFFERS_RANGE_PUBLISH);
        $queueReceiveMessageTransfers = array_merge(
            $this->createQueueReceiveMessageTransfers(MerchantProductOfferWritePublisherPlugin::class, MerchantProductOfferStorageConfig::PRODUCT_OFFER_RANGE_PUBLISH),
            $this->createQueueReceiveMessageTransfers(
                MerchantProductConcreteProductOfferWritePublisherPlugin::class,
                MerchantProductOfferStorageConfig::PRODUCT_CONCRETE_PRODUCT_OFFERS_RANGE_PUBLISH,
            ),
        );

        // Act
        $this->tester->getLocator()->event()->facade()->processEnqueuedMessages($queueReceiveMessageTransfers);

        // Assert
        foreach ([$firstProductOfferTransfer, $secondProductOfferTransfer] as $productOfferTransfer) {
            $this->assertCount(1, $this->tester->getProductOfferEntities($productOfferTransfer->getProductOfferReferenceOrFail()));
            $this->assertCount(1, $this->tester->getProductConcreteProductOffersEntities($productOfferTransfer->getConcreteSkuOrFail()));
        }
    }

    public function testGivenRangeEventWhenPublishingThenOnlyOffersOfTheMerchantInsideTheRangeAreWritten(): void
    {
        // Arrange
        $this->tester->clearProductOfferData();
        $merchantTransfer = $this->tester->haveMerchant([MerchantTransfer::IS_ACTIVE => true]);
        $otherMerchantTransfer = $this->tester->haveMerchant([MerchantTransfer::IS_ACTIVE => true]);
        $firstProductOfferTransfer = $this->haveProductOffer($merchantTransfer, $this->tester->haveProduct()->getSkuOrFail());
        $otherMerchantProductOfferTransfer = $this->haveProductOffer($otherMerchantTransfer, $this->tester->haveProduct()->getSkuOrFail());
        $secondProductOfferTransfer = $this->haveProductOffer($merchantTransfer, $this->tester->haveProduct()->getSkuOrFail());
        $outsideRangeProductOfferTransfer = $this->haveProductOffer($merchantTransfer, $this->tester->haveProduct()->getSkuOrFail());
        $rangeEventEntityTransfers = [
            $this->createRangeEventEntityTransfer(
                $merchantTransfer->getMerchantReferenceOrFail(),
                $firstProductOfferTransfer->getIdProductOfferOrFail(),
                $secondProductOfferTransfer->getIdProductOfferOrFail(),
            ),
        ];

        // Act
        (new MerchantProductOfferWritePublisherPlugin())->handleBulk($rangeEventEntityTransfers, MerchantProductOfferStorageConfig::PRODUCT_OFFER_RANGE_PUBLISH);
        (new MerchantProductConcreteProductOfferWritePublisherPlugin())->handleBulk(
            $rangeEventEntityTransfers,
            MerchantProductOfferStorageConfig::PRODUCT_CONCRETE_PRODUCT_OFFERS_RANGE_PUBLISH,
        );

        // Assert
        foreach ([$firstProductOfferTransfer, $secondProductOfferTransfer] as $productOfferTransfer) {
            $this->assertCount(1, $this->tester->getProductOfferEntities($productOfferTransfer->getProductOfferReferenceOrFail()));
            $this->assertCount(1, $this->tester->getProductConcreteProductOffersEntities($productOfferTransfer->getConcreteSkuOrFail()));
        }
        foreach ([$otherMerchantProductOfferTransfer, $outsideRangeProductOfferTransfer] as $productOfferTransfer) {
            $this->assertCount(0, $this->tester->getProductOfferEntities($productOfferTransfer->getProductOfferReferenceOrFail()));
            $this->assertCount(0, $this->tester->getProductConcreteProductOffersEntities($productOfferTransfer->getConcreteSkuOrFail()));
        }
        $this->assertSame([], $this->triggeredEventEntityTransfers);
    }

    public function testGivenTwoRangeEventsOfOneMerchantWhenPublishingThenTheOfferBetweenTheRangesIsNotWritten(): void
    {
        // Arrange
        $this->tester->clearProductOfferData();
        $merchantTransfer = $this->tester->haveMerchant([MerchantTransfer::IS_ACTIVE => true]);
        $firstProductOfferTransfer = $this->haveProductOffer($merchantTransfer, $this->tester->haveProduct()->getSkuOrFail());
        $betweenRangesProductOfferTransfer = $this->haveProductOffer($merchantTransfer, $this->tester->haveProduct()->getSkuOrFail());
        $secondProductOfferTransfer = $this->haveProductOffer($merchantTransfer, $this->tester->haveProduct()->getSkuOrFail());
        $rangeEventEntityTransfers = [
            $this->createRangeEventEntityTransfer(
                $merchantTransfer->getMerchantReferenceOrFail(),
                $firstProductOfferTransfer->getIdProductOfferOrFail(),
                $firstProductOfferTransfer->getIdProductOfferOrFail(),
            ),
            $this->createRangeEventEntityTransfer(
                $merchantTransfer->getMerchantReferenceOrFail(),
                $secondProductOfferTransfer->getIdProductOfferOrFail(),
                $secondProductOfferTransfer->getIdProductOfferOrFail(),
            ),
        ];

        // Act
        (new MerchantProductOfferWritePublisherPlugin())->handleBulk($rangeEventEntityTransfers, MerchantProductOfferStorageConfig::PRODUCT_OFFER_RANGE_PUBLISH);
        (new MerchantProductConcreteProductOfferWritePublisherPlugin())->handleBulk(
            $rangeEventEntityTransfers,
            MerchantProductOfferStorageConfig::PRODUCT_CONCRETE_PRODUCT_OFFERS_RANGE_PUBLISH,
        );

        // Assert
        foreach ([$firstProductOfferTransfer, $secondProductOfferTransfer] as $productOfferTransfer) {
            $this->assertCount(1, $this->tester->getProductOfferEntities($productOfferTransfer->getProductOfferReferenceOrFail()));
            $this->assertCount(1, $this->tester->getProductConcreteProductOffersEntities($productOfferTransfer->getConcreteSkuOrFail()));
        }
        $this->assertCount(0, $this->tester->getProductOfferEntities($betweenRangesProductOfferTransfer->getProductOfferReferenceOrFail()));
        $this->assertCount(0, $this->tester->getProductConcreteProductOffersEntities($betweenRangesProductOfferTransfer->getConcreteSkuOrFail()));
    }

    public function testGivenRangeEventWithoutOffersOfTheMerchantWhenPublishingThenNothingIsWritten(): void
    {
        // Arrange
        $this->tester->clearProductOfferData();
        $merchantTransfer = $this->tester->haveMerchant([MerchantTransfer::IS_ACTIVE => true]);
        $otherMerchantTransfer = $this->tester->haveMerchant([MerchantTransfer::IS_ACTIVE => true]);
        $productOfferTransfer = $this->haveProductOffer($otherMerchantTransfer, $this->tester->haveProduct()->getSkuOrFail());
        $rangeEventEntityTransfers = [
            $this->createRangeEventEntityTransfer(
                $merchantTransfer->getMerchantReferenceOrFail(),
                $productOfferTransfer->getIdProductOfferOrFail(),
                $productOfferTransfer->getIdProductOfferOrFail(),
            ),
        ];

        // Act
        (new MerchantProductOfferWritePublisherPlugin())->handleBulk($rangeEventEntityTransfers, MerchantProductOfferStorageConfig::PRODUCT_OFFER_RANGE_PUBLISH);
        (new MerchantProductConcreteProductOfferWritePublisherPlugin())->handleBulk(
            $rangeEventEntityTransfers,
            MerchantProductOfferStorageConfig::PRODUCT_CONCRETE_PRODUCT_OFFERS_RANGE_PUBLISH,
        );

        // Assert
        $this->assertCount(0, $this->tester->getProductOfferEntities($productOfferTransfer->getProductOfferReferenceOrFail()));
        $this->assertCount(0, $this->tester->getProductConcreteProductOffersEntities($productOfferTransfer->getConcreteSkuOrFail()));
    }

    /**
     * @dataProvider getProductOfferStorageRelevantMerchantColumnsDataProvider
     */
    public function testGivenMerchantEventWithRelevantModifiedColumnWhenPublishingThenBothPluginsTriggerRangeEvents(string $modifiedColumn): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveMerchant([MerchantTransfer::IS_ACTIVE => true]);
        $this->haveProductOffer($merchantTransfer, $this->tester->haveProduct()->getSkuOrFail());
        $eventEntityTransfers = [
            (new EventEntityTransfer())
                ->setId($merchantTransfer->getIdMerchantOrFail())
                ->setModifiedColumns([$modifiedColumn]),
        ];

        // Act
        (new MerchantProductConcreteProductOfferWritePublisherPlugin())->handleBulk($eventEntityTransfers, MerchantProductOfferStorageConfig::MERCHANT_PUBLISH);
        (new MerchantProductOfferWritePublisherPlugin())->handleBulk($eventEntityTransfers, MerchantProductOfferStorageConfig::MERCHANT_PUBLISH);

        // Assert
        $this->assertSame(
            [$merchantTransfer->getMerchantReferenceOrFail()],
            $this->getTriggeredMerchantReferences(MerchantProductOfferStorageConfig::PRODUCT_CONCRETE_PRODUCT_OFFERS_RANGE_PUBLISH),
        );
        $this->assertSame(
            [$merchantTransfer->getMerchantReferenceOrFail()],
            $this->getTriggeredMerchantReferences(MerchantProductOfferStorageConfig::PRODUCT_OFFER_RANGE_PUBLISH),
        );
    }

    /**
     * @return array<string, array<string>>
     */
    public function getProductOfferStorageRelevantMerchantColumnsDataProvider(): array
    {
        $dataSets = [];
        foreach ((new ZedMerchantProductOfferStorageConfig())->getProductOfferStorageRelevantMerchantColumns() as $modifiedColumn) {
            $dataSets[$modifiedColumn] = [$modifiedColumn];
        }

        return $dataSets;
    }

    /**
     * @dataProvider getIrrelevantMerchantModifiedColumnsDataProvider
     *
     * @param list<string> $modifiedColumns
     */
    public function testGivenMerchantEventWithIrrelevantModifiedColumnsWhenPublishingThenBothPluginsTriggerNothing(array $modifiedColumns): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveMerchant([MerchantTransfer::IS_ACTIVE => true]);
        $this->haveProductOffer($merchantTransfer, $this->tester->haveProduct()->getSkuOrFail());
        $eventEntityTransfers = [
            (new EventEntityTransfer())
                ->setId($merchantTransfer->getIdMerchantOrFail())
                ->setModifiedColumns($modifiedColumns),
        ];

        // Act
        (new MerchantProductConcreteProductOfferWritePublisherPlugin())->handleBulk($eventEntityTransfers, MerchantProductOfferStorageConfig::MERCHANT_PUBLISH);
        (new MerchantProductOfferWritePublisherPlugin())->handleBulk($eventEntityTransfers, MerchantProductOfferStorageConfig::MERCHANT_PUBLISH);

        // Assert
        $this->assertSame([], $this->triggeredEventEntityTransfers);
    }

    /**
     * @return array<string, array<list<string>>>
     */
    public function getIrrelevantMerchantModifiedColumnsDataProvider(): array
    {
        return [
            'merchant related data marker' => [[MerchantConfig::MODIFIED_COLUMN_MERCHANT_RELATED_DATA]],
            'merchant store relation' => [[SpyMerchantStoreTableMap::COL_FK_STORE]],
            'name and updated at' => [[SpyMerchantTableMap::COL_NAME, SpyMerchantTableMap::COL_UPDATED_AT]],
            'email' => [[SpyMerchantTableMap::COL_EMAIL]],
        ];
    }

    public function testGivenMixedMerchantEventsWhenPublishingThenOnlyMerchantsWithoutColumnsOrWithRelevantColumnsGetRangeEvents(): void
    {
        // Arrange
        $merchantWithoutColumnsTransfer = $this->tester->haveMerchant([MerchantTransfer::IS_ACTIVE => true]);
        $merchantWithIrrelevantColumnTransfer = $this->tester->haveMerchant([MerchantTransfer::IS_ACTIVE => true]);
        $merchantWithRelevantColumnTransfer = $this->tester->haveMerchant([MerchantTransfer::IS_ACTIVE => true]);
        $this->haveProductOffer($merchantWithoutColumnsTransfer, $this->tester->haveProduct()->getSkuOrFail());
        $this->haveProductOffer($merchantWithIrrelevantColumnTransfer, $this->tester->haveProduct()->getSkuOrFail());
        $this->haveProductOffer($merchantWithRelevantColumnTransfer, $this->tester->haveProduct()->getSkuOrFail());
        $expectedMerchantReferences = [
            $merchantWithoutColumnsTransfer->getMerchantReferenceOrFail(),
            $merchantWithRelevantColumnTransfer->getMerchantReferenceOrFail(),
        ];
        sort($expectedMerchantReferences);

        $eventEntityTransfers = [
            (new EventEntityTransfer())->setId($merchantWithoutColumnsTransfer->getIdMerchantOrFail()),
            (new EventEntityTransfer())
                ->setId($merchantWithIrrelevantColumnTransfer->getIdMerchantOrFail())
                ->setModifiedColumns([SpyMerchantTableMap::COL_NAME]),
            (new EventEntityTransfer())
                ->setId($merchantWithRelevantColumnTransfer->getIdMerchantOrFail())
                ->setModifiedColumns([SpyMerchantTableMap::COL_IS_ACTIVE]),
        ];

        // Act
        (new MerchantProductOfferWritePublisherPlugin())->handleBulk($eventEntityTransfers, MerchantProductOfferStorageConfig::MERCHANT_PUBLISH);

        // Assert
        $triggeredMerchantReferences = $this->getTriggeredMerchantReferences(MerchantProductOfferStorageConfig::PRODUCT_OFFER_RANGE_PUBLISH);
        sort($triggeredMerchantReferences);
        $this->assertSame($expectedMerchantReferences, $triggeredMerchantReferences);
    }

    protected function createProductOfferRangeEventTrigger(int $rangeSize, int $readBatchSize): ProductOfferRangeEventTrigger
    {
        /** @var \SprykerTest\Zed\Testify\Helper\Business\BusinessHelper $businessHelper */
        $businessHelper = $this->getModule('\\' . BusinessHelper::class);

        /** @var \Spryker\Zed\MerchantProductOfferStorage\Business\MerchantProductOfferStorageBusinessFactory $merchantProductOfferStorageBusinessFactory */
        $merchantProductOfferStorageBusinessFactory = $businessHelper->getFactory('MerchantProductOfferStorage');

        return new ProductOfferRangeEventTrigger(
            $merchantProductOfferStorageBusinessFactory->getEventBehaviorFacade(),
            new MerchantProductOfferStorageRepository(),
            $merchantProductOfferStorageBusinessFactory->getEventFacade(),
            $this->createMerchantProductOfferStorageConfig($rangeSize, $readBatchSize),
        );
    }

    protected function createMerchantProductOfferStorageConfig(int $rangeSize, int $readBatchSize): ZedMerchantProductOfferStorageConfig
    {
        return new class ($rangeSize, $readBatchSize) extends ZedMerchantProductOfferStorageConfig {
            public function __construct(protected int $rangeSize, protected int $readBatchSize)
            {
            }

            public function getMerchantProductOfferRangeSize(): int
            {
                return $this->rangeSize;
            }

            public function getMerchantProductOfferRangeReadBatchSize(): int
            {
                return $this->readBatchSize;
            }
        };
    }

    protected function publishTriggeredRangeEvents(): void
    {
        $triggeredEventEntityTransfers = $this->triggeredEventEntityTransfers;
        $publisherPluginsByEventName = [
            MerchantProductOfferStorageConfig::PRODUCT_OFFER_RANGE_PUBLISH => new MerchantProductOfferWritePublisherPlugin(),
            MerchantProductOfferStorageConfig::PRODUCT_CONCRETE_PRODUCT_OFFERS_RANGE_PUBLISH => new MerchantProductConcreteProductOfferWritePublisherPlugin(),
        ];

        $this->assertSame(array_keys($publisherPluginsByEventName), array_keys($triggeredEventEntityTransfers));

        foreach ($publisherPluginsByEventName as $eventName => $publisherPlugin) {
            $publisherPlugin->handleBulk($triggeredEventEntityTransfers[$eventName], $eventName);
        }
    }

    /**
     * @return list<\Generated\Shared\Transfer\QueueReceiveMessageTransfer>
     */
    protected function createQueueReceiveMessageTransfers(string $listenerClassName, string $eventName): array
    {
        $queueReceiveMessageTransfers = [];
        foreach ($this->triggeredEventEntityTransfers[$eventName] as $eventEntityTransfer) {
            $messageBody = [
                EventQueueSendMessageBodyTransfer::LISTENER_CLASS_NAME => $listenerClassName,
                EventQueueSendMessageBodyTransfer::TRANSFER_CLASS_NAME => EventEntityTransfer::class,
                EventQueueSendMessageBodyTransfer::TRANSFER_DATA => $eventEntityTransfer->toArray(),
                EventQueueSendMessageBodyTransfer::EVENT_NAME => $eventName,
            ];

            $queueReceiveMessageTransfers[] = (new QueueReceiveMessageTransfer())
                ->setQueueMessage((new QueueSendMessageTransfer())->setBody((string)json_encode($messageBody)));
        }

        return $queueReceiveMessageTransfers;
    }

    protected function createRangeEventEntityTransfer(string $merchantReference, int $fromIdProductOffer, int $toIdProductOffer): EventEntityTransfer
    {
        return (new EventEntityTransfer())->setAdditionalValues([
            MerchantProductOfferStorageConfig::RANGE_MERCHANT_REFERENCE => $merchantReference,
            MerchantProductOfferStorageConfig::RANGE_FROM_ID_PRODUCT_OFFER => $fromIdProductOffer,
            MerchantProductOfferStorageConfig::RANGE_TO_ID_PRODUCT_OFFER => $toIdProductOffer,
        ]);
    }

    protected function haveProductOffer(MerchantTransfer $merchantTransfer, string $concreteSku): ProductOfferTransfer
    {
        $storeTransfer = $this->tester->haveStore([StoreTransfer::NAME => 'DE']);
        $productOfferTransfer = $this->tester->haveProductOffer([
            ProductOfferTransfer::MERCHANT_REFERENCE => $merchantTransfer->getMerchantReferenceOrFail(),
            ProductOfferTransfer::CONCRETE_SKU => $concreteSku,
        ])->addStore($storeTransfer);
        $this->tester->haveProductOfferStore($productOfferTransfer, $storeTransfer);

        return $productOfferTransfer;
    }

    /**
     * @return list<string>
     */
    protected function getTriggeredMerchantReferences(string $eventName): array
    {
        $merchantReferences = [];
        foreach ($this->triggeredEventEntityTransfers[$eventName] ?? [] as $eventEntityTransfer) {
            $merchantReferences[] = $eventEntityTransfer->getAdditionalValues()[MerchantProductOfferStorageConfig::RANGE_MERCHANT_REFERENCE];
        }

        return array_values(array_unique($merchantReferences));
    }
}
