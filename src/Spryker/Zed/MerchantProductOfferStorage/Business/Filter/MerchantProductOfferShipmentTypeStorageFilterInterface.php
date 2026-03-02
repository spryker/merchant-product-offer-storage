<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\MerchantProductOfferStorage\Business\Filter;

use Generated\Shared\Transfer\ProductOfferShipmentTypeCollectionTransfer;

interface MerchantProductOfferShipmentTypeStorageFilterInterface
{
    public function filterProductOfferShipmentTypeCollection(
        ProductOfferShipmentTypeCollectionTransfer $productOfferShipmentTypeCollectionTransfer
    ): ProductOfferShipmentTypeCollectionTransfer;
}
