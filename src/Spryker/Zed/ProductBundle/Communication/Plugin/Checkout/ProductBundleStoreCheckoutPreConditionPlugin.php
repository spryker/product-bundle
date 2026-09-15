<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ProductBundle\Communication\Plugin\Checkout;

use Generated\Shared\Transfer\CheckoutResponseTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Zed\CheckoutExtension\Dependency\Plugin\CheckoutPreConditionPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * @method \Spryker\Zed\ProductBundle\Business\ProductBundleBusinessFactory getBusinessFactory()
 * @method \Spryker\Zed\ProductBundle\Business\ProductBundleFacadeInterface getFacade()
 * @method \Spryker\Zed\ProductBundle\Communication\ProductBundleCommunicationFactory getFactory()
 * @method \Spryker\Zed\ProductBundle\ProductBundleConfig getConfig()
 * @method \Spryker\Zed\ProductBundle\Persistence\ProductBundleQueryContainerInterface getQueryContainer()
 */
class ProductBundleStoreCheckoutPreConditionPlugin extends AbstractPlugin implements CheckoutPreConditionPluginInterface
{
    /**
     * {@inheritDoc}
     * - Requires `QuoteTransfer.store.name` to be set when `QuoteTransfer.bundleItems` is not empty.
     * - Validates the product bundles from `QuoteTransfer.bundleItems`, deduplicated by `QuoteTransfer.bundleItem.groupKey`.
     * - Checks that the bundle product itself is assigned to the store of `QuoteTransfer.store`.
     * - Checks that every bundled product of the bundle is assigned to the store of `QuoteTransfer.store`.
     * - Returns `true` if all bundles and their bundled products are assigned to the store.
     * - Otherwise, returns `false`, sets `CheckoutResponseTransfer.isSuccess` to `false` and adds error messages to the `CheckoutResponseTransfer`.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     * @param \Generated\Shared\Transfer\CheckoutResponseTransfer $checkoutResponseTransfer
     *
     * @return bool
     */
    public function checkCondition(QuoteTransfer $quoteTransfer, CheckoutResponseTransfer $checkoutResponseTransfer): bool
    {
        return $this->getBusinessFactory()
            ->createProductBundleStoreCheckoutCheck()
            ->checkCheckoutStoreAssignment($quoteTransfer, $checkoutResponseTransfer);
    }
}
