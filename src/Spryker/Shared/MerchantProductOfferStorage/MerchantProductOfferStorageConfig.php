<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\MerchantProductOfferStorage;

/**
 * Declares global environment configuration keys. Do not use it for other class constants.
 */
class MerchantProductOfferStorageConfig
{
    /**
     * Specification
     * - This events will be used for spy_merchant publishing.
     *
     * @api
     *
     * @uses \Spryker\Zed\Merchant\Dependency\MerchantEvents::MERCHANT_PUBLISH
     *
     * @var string
     */
    public const MERCHANT_PUBLISH = 'Merchant.merchant.publish';

    /**
     * Specification
     * - This events will be used for spy_merchant entity changes.
     *
     * @api
     *
     * @uses \Spryker\Zed\Merchant\Dependency\MerchantEvents::ENTITY_SPY_MERCHANT_UPDATE
     *
     * @var string
     */
    public const ENTITY_SPY_MERCHANT_UPDATE = 'Entity.spy_merchant.update';

    /**
     * Specification:
     * - This event is used to publish the product offer storage of the product offers of one merchant in one product offer ID range.
     * - The additional values hold the merchant reference and the first and the last product offer ID of the range, both inclusive.
     * - The event has no ID, so the event queue consumer does not merge the range events of one merchant.
     *
     * @api
     */
    public const string PRODUCT_OFFER_RANGE_PUBLISH = 'MerchantProductOfferStorage.product_offer_range.publish';

    /**
     * Specification:
     * - This event is used to publish the product concrete product offers storage of the concrete products that have product offers of one merchant in one product offer ID range.
     * - The additional values hold the merchant reference and the first and the last product offer ID of the range, both inclusive.
     * - The event has no ID, so the event queue consumer does not merge the range events of one merchant.
     *
     * @api
     */
    public const string PRODUCT_CONCRETE_PRODUCT_OFFERS_RANGE_PUBLISH = 'MerchantProductOfferStorage.product_concrete_product_offers_range.publish';

    /**
     * Specification:
     * - Additional value key of a range event: the merchant reference of the product offers.
     *
     * @api
     */
    public const string RANGE_MERCHANT_REFERENCE = 'merchant_reference';

    /**
     * Specification:
     * - Additional value key of a range event: the first product offer ID of the range, inclusive.
     *
     * @api
     */
    public const string RANGE_FROM_ID_PRODUCT_OFFER = 'from_id_product_offer';

    /**
     * Specification:
     * - Additional value key of a range event: the last product offer ID of the range, inclusive.
     *
     * @api
     */
    public const string RANGE_TO_ID_PRODUCT_OFFER = 'to_id_product_offer';
}
