<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ProductBundle\Business;

use Generated\Shared\Transfer\CalculableObjectTransfer;
use Generated\Shared\Transfer\CartChangeTransfer;
use Generated\Shared\Transfer\CartPreCheckResponseTransfer;
use Generated\Shared\Transfer\CheckoutResponseTransfer;
use Generated\Shared\Transfer\ItemCollectionTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\OrderTransfer;
use Generated\Shared\Transfer\ProductBundleCollectionTransfer;
use Generated\Shared\Transfer\ProductBundleCriteriaFilterTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\SaveOrderTransfer;

/**
 * @method \Spryker\Zed\ProductBundle\Business\ProductBundleBusinessFactory getFactory()
 */
interface ProductBundleFacadeInterface
{
    /**
     * Specification:
     * - Takes all items to be added to cart and checks if any is bundle item.
     * - If bundle item then it is removed, and added to QuoteTransfer::bundleItems, the identifier assigned.
     * - Finds all bundled items from that bundle and puts into add to cart operation, assign bundle identifier they belong to.
     * - The price amount is assigned, proportionally split through items quantity = 1.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\CartChangeTransfer $cartChangeTransfer
     *
     * @return \Generated\Shared\Transfer\CartChangeTransfer
     */
    public function expandBundleItems(CartChangeTransfer $cartChangeTransfer);

    /**
     * Specification:
     * - Requires `CartChangeTransfer.quote.priceMode` to be set.
     * - Requires `id`, `sku`, `groupKey`, `quantity` and price (depending on mode) to be set for each element in `CartChangeTransfer.items`.
     * - Moves bundles from `CartChangeTransfer.items` to `CartChangeTransfer.quote.bundleItems` and adds bundled items instead.
     * - New bundle identifiers are assigned to bundles, which were moved to `CartChangeTransfer.quote.bundleItems`.
     * - Bundle price is distributed proportionally between all bundled items.
     * - Bundled items get into `CartChangeTransfer.items` united in one piece with a corresponding quantity,
     *   instead of being added individually with a quantity of 1. I.e. a bundle in `CartChangeTransfer.items`
     *   with a quantity of 3 will be replaced with groups of bundled items, each group also having a quantity of 3.
     * - Used instead of `expandBundleItems()` method, when united bundled items approach is applied in cart.
     *
     * @api
     *
     * @see {@link \Spryker\Zed\ProductBundle\Business\ProductBundleFacadeInterface::expandBundleItems()}
     *
     * @param \Generated\Shared\Transfer\CartChangeTransfer $cartChangeTransfer
     *
     * @return \Generated\Shared\Transfer\CartChangeTransfer
     */
    public function unfoldBundlesToUnitedItems(CartChangeTransfer $cartChangeTransfer): CartChangeTransfer;

    /**
     * Specification:
     * - It will add images to product bundle.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\CartChangeTransfer $cartChangeTransfer
     *
     * @return \Generated\Shared\Transfer\CartChangeTransfer
     */
    public function expandBundleItemsWithImages(CartChangeTransfer $cartChangeTransfer);

    /**
     * Specification:
     * - The group key is build to uniquely identify bundled items.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\CartChangeTransfer $cartChangeTransfer
     *
     * @return \Generated\Shared\Transfer\CartChangeTransfer
     */
    public function expandBundleCartItemGroupKey(CartChangeTransfer $cartChangeTransfer);

    /**
     * Specification:
     * - Updates QuoteTransfer::bundleItems to be in sync with current existing bundled items in cart.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     *
     * @return \Generated\Shared\Transfer\QuoteTransfer
     */
    public function postSaveCartUpdateBundles(QuoteTransfer $quoteTransfer);

    /**
     * Specification:
     * - Requires `bundleItemIdentifier` to be set for each element in `QuoteTransfer.bundleItems`
     * - Refreshes `QuoteTransfer.bundleItems` to be in sync with current existing bundled items in cart.
     * - Used instead of `postSaveCartUpdateBundles()` method, when united bundled items approach is applied in cart.
     *
     * @api
     *
     * @see {@link \Spryker\Zed\ProductBundle\Business\ProductBundleFacadeInterface::postSaveCartUpdateBundles()}
     *
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     *
     * @return \Generated\Shared\Transfer\QuoteTransfer
     */
    public function refreshBundlesWithUnitedItemsToBeInSyncWithQuote(QuoteTransfer $quoteTransfer): QuoteTransfer;

    /**
     * Specification:
     * - Checks if bundle product bundled items are available.
     * - If bundled products are added separately, it gets checked together with bundled products.
     * - Sets error message if not available.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\CartChangeTransfer $cartChangeTransfer
     *
     * @return \Generated\Shared\Transfer\CartPreCheckResponseTransfer
     */
    public function preCheckCartAvailability(CartChangeTransfer $cartChangeTransfer);

    /**
     * Specification:
     * - Checks if bundled items which being added to cart is active.
     * - Checks if products in the bundle are active.
     * - Sets CartPreCheckResponseTransfer::isSuccess to false if some of products are not active.
     * - Sets error message if some of products are not active.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\CartChangeTransfer $cartChangeTransfer
     *
     * @return \Generated\Shared\Transfer\CartPreCheckResponseTransfer
     */
    public function preCheckCartActive(CartChangeTransfer $cartChangeTransfer): CartPreCheckResponseTransfer;

    /**
     * Specification:
     * - Checks if items which being added to checkout is available, for bundle it checks bundled items.
     * - Even if same item added separately from bundle availability is checked together.
     * - Sets error message if not available.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     * @param \Generated\Shared\Transfer\CheckoutResponseTransfer $checkoutResponseTransfer
     *
     * @return bool
     */
    public function preCheckCheckoutAvailability(QuoteTransfer $quoteTransfer, CheckoutResponseTransfer $checkoutResponseTransfer);

    /**
     * Specification:
     *  - Checks if bundled products of cart items has price for current store.
     *  - Sets error message if prices are not available.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\CartChangeTransfer $cartChangeTransfer
     *
     * @return \Generated\Shared\Transfer\CartPreCheckResponseTransfer
     */
    public function preCheckBundledProductPrices(CartChangeTransfer $cartChangeTransfer): CartPreCheckResponseTransfer;

    /**
     * Specification:
     *  - Calculates QuoteTransfer::bundleItems prices.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     *
     * @return \Generated\Shared\Transfer\QuoteTransfer
     */
    public function calculateBundlePrice(QuoteTransfer $quoteTransfer);

    /**
     * Specification:
     *  - Calculates {@link \Generated\Shared\Transfer\CalculableObjectTransfer::$bundleItems} prices.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\CalculableObjectTransfer $calculableObjectTransfer
     *
     * @return \Generated\Shared\Transfer\CalculableObjectTransfer
     */
    public function calculateBundlePriceForCalculableObjectTransfer(CalculableObjectTransfer $calculableObjectTransfer): CalculableObjectTransfer;

    /**
     * Specification:
     * - Gets all items which belong to bundle.
     * - Updates bundle products with new availability, given sku belong.
     * - Touch abstract availability for bundle product.
     *
     * @api
     *
     * @param string $concreteSku
     *
     * @return void
     */
    public function updateAffectedBundlesAvailability($concreteSku);

    /**
     * Specification:
     * - Gets all items which belong to bundle.
     * - Updates bundle products with new stock, given sku belong.
     * - Touch abstract stock for bundle product.
     *
     * @api
     *
     * @param string $concreteSku
     *
     * @return void
     */
    public function updateAffectedBundlesStock($concreteSku): void;

    /**
     * Specification:
     *  - Calculated bundle availability based on bundled items.
     *  - Persists availability.
     *  - Touches availability abstract collector for bundle.
     *
     * @api
     *
     * @param string $productBundleSku
     *
     * @return void
     */
    public function updateBundleAvailability($productBundleSku);

    /**
     * Specification:
     * - Deactivates product bundles related to product concrete in case it is inactive.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\ProductConcreteTransfer $productConcreteTransfer
     *
     * @return \Generated\Shared\Transfer\ProductConcreteTransfer
     */
    public function deactivateRelatedProductBundles(ProductConcreteTransfer $productConcreteTransfer): ProductConcreteTransfer;

    /**
     * Specification:
     * - Persists bundled product to sales database tables, from QuoteTransfer.
     *
     * @api
     *
     * @deprecated Use {@link saveOrderBundleItems()} instead.
     *
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     * @param \Generated\Shared\Transfer\CheckoutResponseTransfer $checkoutResponse
     *
     * @return void
     */
    public function saveSalesOrderBundleItems(QuoteTransfer $quoteTransfer, CheckoutResponseTransfer $checkoutResponse);

    /**
     * Specification:
     * - Persists bundled product to sales database tables, from QuoteTransfer.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     * @param \Generated\Shared\Transfer\SaveOrderTransfer $saveOrderTransfer
     *
     * @return void
     */
    public function saveOrderBundleItems(QuoteTransfer $quoteTransfer, SaveOrderTransfer $saveOrderTransfer);

    /**
     * Specification:
     * - Persists bundled products within ProductConcrete.
     * - Updates product bundle available stock.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\ProductConcreteTransfer $productConcreteTransfer
     *
     * @return \Generated\Shared\Transfer\ProductConcreteTransfer
     */
    public function saveBundledProducts(ProductConcreteTransfer $productConcreteTransfer);

    /**
     * Specification:
     * - Finds all bundled products to given concrete product.
     *
     * @api
     *
     * @param int $idProductConcrete
     *
     * @return \ArrayObject<int, \Generated\Shared\Transfer\ProductForBundleTransfer>
     */
    public function findBundledProductsByIdProductConcrete($idProductConcrete);

    /**
     * Specification:
     * - Gets bundle product collection by criteria filter.
     * - Returns bundle product collection with all bundle products if Criteria Filter is empty.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\ProductBundleCriteriaFilterTransfer $productBundleCriteriaFilterTransfer
     *
     * @return \Generated\Shared\Transfer\ProductBundleCollectionTransfer
     */
    public function getProductBundleCollectionByCriteriaFilter(
        ProductBundleCriteriaFilterTransfer $productBundleCriteriaFilterTransfer
    ): ProductBundleCollectionTransfer;

    /**
     * Specification:
     * - Assigns bundled products to ProductConcreteTransfer::productBundle.
     * - Returns modified ProductConcreteTransfer.
     *
     * @api
     *
     * @deprecated Use {@link \Spryker\Zed\ProductBundle\Business\ProductBundleFacadeInterface::expandProductConcreteTransfersWithBundledProducts()} instead.
     *
     * @param \Generated\Shared\Transfer\ProductConcreteTransfer $productConcreteTransfer
     *
     * @return \Generated\Shared\Transfer\ProductConcreteTransfer
     */
    public function assignBundledProductsToProductConcrete(ProductConcreteTransfer $productConcreteTransfer);

    /**
     * Specification:
     * - Expands transfers of product concrete with bundled products.
     * - Returns modified ProductConcreteTransfers.
     *
     * @api
     *
     * @param array<\Generated\Shared\Transfer\ProductConcreteTransfer> $productConcreteTransfers
     *
     * @return array<\Generated\Shared\Transfer\ProductConcreteTransfer>
     */
    public function expandProductConcreteTransfersWithBundledProducts(array $productConcreteTransfers): array;

    /**
     * Specification:
     *  - Hydrates OrderTransfer with product bundle data.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\OrderTransfer $orderTransfer
     *
     * @return \Generated\Shared\Transfer\OrderTransfer
     */
    public function hydrateSalesOrderProductBundles(OrderTransfer $orderTransfer);

    /**
     * Specification:
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\OrderTransfer $orderTransfer
     *
     * @return \Generated\Shared\Transfer\OrderTransfer
     */
    public function hydrateProductBundleIds(OrderTransfer $orderTransfer);

    /**
     * Specification:
     *  - Filter bundle items after cart item reload operation is called.
     *  - Bundled items are removed from cart.
     *  - Bundle item are added as new add so new prices can be assigned.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     *
     * @return \Generated\Shared\Transfer\QuoteTransfer
     */
    public function filterBundleItemsOnCartReload(QuoteTransfer $quoteTransfer);

    /**
     * Specification:
     * - Replace quote items with bundle if it is possible.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\CartChangeTransfer $cartChangeTransfer
     *
     * @return \Generated\Shared\Transfer\CartChangeTransfer
     */
    public function replaceItemsWithBundleItems(CartChangeTransfer $cartChangeTransfer): CartChangeTransfer;

    /**
     * Specification:
     * - Requires `CartChangeTransfer.quote` to be set.
     * - Requires `groupKey` and `quantity` to be set for each element in `CartChangeTransfer.items`.
     * - Replaces bundles in `CartChangeTransfer.items` with corresponding bundled items.
     * - Bundled items get into `CartChangeTransfer.items` united in one piece with a corresponding quantity,
     *   instead of being added individually with a quantity of 1. I.e. a bundle in `CartChangeTransfer.items`
     *   with a quantity of 3 will be replaced with groups of bundled items, each group also having a quantity of 3.
     * - Used instead of `replaceItemsWithBundleItems()` method, when united bundled items approach is applied in cart.
     *
     * @api
     *
     * @see {@link \Spryker\Zed\ProductBundle\Business\ProductBundleFacadeInterface::replaceItemsWithBundleItems()}
     *
     * @param \Generated\Shared\Transfer\CartChangeTransfer $cartChangeTransfer
     *
     * @return \Generated\Shared\Transfer\CartChangeTransfer
     */
    public function replaceBundlesWithUnitedItems(CartChangeTransfer $cartChangeTransfer): CartChangeTransfer;

    /**
     * Specification:
     * - Find bundle item in quote.
     * - Clone item.
     * - Take sum of all bundle items of the same group.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     * @param string $sku
     * @param string|null $groupKey
     *
     * @return \Generated\Shared\Transfer\ItemTransfer|null
     */
    public function findItemInQuote(QuoteTransfer $quoteTransfer, $sku, $groupKey): ?ItemTransfer;

    /**
     * Specification:
     * - Checks price difference between quotes bundle items.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\QuoteTransfer $resultQuoteTransfer
     * @param \Generated\Shared\Transfer\QuoteTransfer $sourceQuoteTransfer
     *
     * @return void
     */
    public function checkBundleItemsPriceChanges(QuoteTransfer $resultQuoteTransfer, QuoteTransfer $sourceQuoteTransfer): void;

    /**
     * Specification:
     *  - Find bundled items in quote.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     * @param string $sku
     * @param string|null $groupKey
     *
     * @return array<\Generated\Shared\Transfer\ItemTransfer>
     */
    public function findBundleItemsInQuote(QuoteTransfer $quoteTransfer, $sku, $groupKey): array;

    /**
     * Specification:
     *  - Find all items in quote.
     *  - Group bundle items as one.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     *
     * @return \Generated\Shared\Transfer\ItemCollectionTransfer
     */
    public function extractQuoteItems(QuoteTransfer $quoteTransfer): ItemCollectionTransfer;

    /**
     * Specification:
     * - Expands sales order bundle items by product options.
     * - Copies unique product options from related bundle items to bundle.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\OrderTransfer $orderTransfer
     *
     * @return \Generated\Shared\Transfer\OrderTransfer
     */
    public function expandOrderProductBundlesWithProductOptions(OrderTransfer $orderTransfer): OrderTransfer;

    /**
     * Specification:
     * - Removes items from array related to bundles.
     * - Expands provided array of ItemTransfers by product bundles.
     *
     * @api
     *
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     * @param \Generated\Shared\Transfer\OrderTransfer $orderTransfer
     *
     * @return array<\Generated\Shared\Transfer\ItemTransfer>
     */
    public function expandUniqueOrderItemsWithProductBundles(array $itemTransfers, OrderTransfer $orderTransfer): array;

    /**
     * Specification:
     * - Expands items with product bundles.
     *
     * @api
     *
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     *
     * @return array<\Generated\Shared\Transfer\ItemTransfer>
     */
    public function expandItemsWithProductBundles(array $itemTransfers): array;

    /**
     * Specification:
     * - Expands item product bundle with product options.
     * - Copies unique product options from related bundle items to bundle.
     * - Expects ItemTransfer::productBundle to be set.
     * - Expects ItemTransfer::relatedBundleItemIdentifier to be set.
     *
     * @api
     *
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     *
     * @return array<\Generated\Shared\Transfer\ItemTransfer>
     */
    public function expandItemProductBundlesWithProductOptions(array $itemTransfers): array;
}
