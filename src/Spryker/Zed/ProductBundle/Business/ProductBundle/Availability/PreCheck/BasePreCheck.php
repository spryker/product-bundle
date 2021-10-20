<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ProductBundle\Business\ProductBundle\Availability\PreCheck;

use ArrayObject;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Spryker\DecimalObject\Decimal;
use Spryker\Zed\ProductBundle\Dependency\Facade\ProductBundleToAvailabilityFacadeInterface;
use Spryker\Zed\ProductBundle\Dependency\Facade\ProductBundleToStoreFacadeInterface;
use Spryker\Zed\ProductBundle\Persistence\ProductBundleQueryContainerInterface;
use Spryker\Zed\ProductBundle\ProductBundleConfig;

class BasePreCheck
{
    /**
     * @var string
     */
    protected const ERROR_BUNDLE_ITEM_UNAVAILABLE_TRANSLATION_KEY = 'product_bundle.unavailable';

    /**
     * @var string
     */
    protected const ERROR_BUNDLE_ITEM_UNAVAILABLE_PARAMETER_BUNDLE_SKU = '%bundleSku%';

    /**
     * @var string
     */
    protected const ERROR_BUNDLE_ITEM_UNAVAILABLE_PARAMETER_PRODUCT_SKU = '%productSku%';

    /**
     * @var \Spryker\Zed\ProductBundle\Dependency\Facade\ProductBundleToAvailabilityFacadeInterface
     */
    protected $availabilityFacade;

    /**
     * @var \Spryker\Zed\ProductBundle\Persistence\ProductBundleQueryContainerInterface
     */
    protected $productBundleQueryContainer;

    /**
     * @var \Spryker\Zed\ProductBundle\Dependency\Facade\ProductBundleToStoreFacadeInterface
     */
    protected $storeFacade;

    /**
     * @var \Spryker\Zed\ProductBundle\ProductBundleConfig
     */
    protected $productBundleConfig;

    /**
     * @param \Spryker\Zed\ProductBundle\Dependency\Facade\ProductBundleToAvailabilityFacadeInterface $availabilityFacade
     * @param \Spryker\Zed\ProductBundle\Persistence\ProductBundleQueryContainerInterface $productBundleQueryContainer
     * @param \Spryker\Zed\ProductBundle\Dependency\Facade\ProductBundleToStoreFacadeInterface $storeFacade
     * @param \Spryker\Zed\ProductBundle\ProductBundleConfig $productBundleConfig
     */
    public function __construct(
        ProductBundleToAvailabilityFacadeInterface $availabilityFacade,
        ProductBundleQueryContainerInterface $productBundleQueryContainer,
        ProductBundleToStoreFacadeInterface $storeFacade,
        ProductBundleConfig $productBundleConfig
    ) {
        $this->availabilityFacade = $availabilityFacade;
        $this->productBundleQueryContainer = $productBundleQueryContainer;
        $this->storeFacade = $storeFacade;
        $this->productBundleConfig = $productBundleConfig;
    }

    /**
     * @param string $sku
     *
     * @return array<\Orm\Zed\ProductBundle\Persistence\SpyProductBundle>
     */
    protected function findBundledProducts(string $sku): array
    {
        return $this->productBundleQueryContainer
            ->queryBundleProductBySku($sku)
            ->find()
            ->getData();
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     * @param array<\Orm\Zed\ProductBundle\Persistence\SpyProductBundle> $bundledProducts
     * @param \Generated\Shared\Transfer\ItemTransfer $itemTransfer
     * @param \Generated\Shared\Transfer\StoreTransfer $storeTransfer
     *
     * @return array
     */
    protected function getUnavailableBundleItems(
        ArrayObject $itemTransfers,
        array $bundledProducts,
        ItemTransfer $itemTransfer,
        StoreTransfer $storeTransfer
    ) {
        $unavailableBundleItems = [];

        foreach ($bundledProducts as $productBundleEntity) {
            $bundledProductConcreteEntity = $productBundleEntity->getSpyProductRelatedByFkBundledProduct();

            $sku = $bundledProductConcreteEntity->getSku();
            $totalBundledItemQuantity = $productBundleEntity->getQuantity() * $itemTransfer->getQuantity();
            if ($this->checkIfItemIsSellable($itemTransfers, $sku, $storeTransfer, new Decimal($totalBundledItemQuantity)) && $bundledProductConcreteEntity->getIsActive()) {
                continue;
            }
            $unavailableBundleItems[] = [
                static::ERROR_BUNDLE_ITEM_UNAVAILABLE_PARAMETER_BUNDLE_SKU => $itemTransfer->getSku(),
                static::ERROR_BUNDLE_ITEM_UNAVAILABLE_PARAMETER_PRODUCT_SKU => $sku,
            ];
        }

        return $unavailableBundleItems;
    }

    /**
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     * @param string $sku
     * @param \Generated\Shared\Transfer\StoreTransfer $storeTransfer
     * @param \Spryker\DecimalObject\Decimal|null $itemQuantity
     *
     * @return bool
     */
    protected function checkIfItemIsSellable(
        iterable $itemTransfers,
        string $sku,
        StoreTransfer $storeTransfer,
        ?Decimal $itemQuantity = null
    ): bool {
        if ($itemQuantity === null) {
            $itemQuantity = new Decimal(0);
        }

        $currentItemQuantity = $this->getAccumulatedItemQuantityForGivenSku($itemTransfers, $sku);
        $currentItemQuantity = $currentItemQuantity->add($itemQuantity);

        return $this->availabilityFacade->isProductSellableForStore($sku, $currentItemQuantity, $storeTransfer);
    }

    /**
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     * @param string $sku
     *
     * @return \Spryker\DecimalObject\Decimal
     */
    protected function getAccumulatedItemQuantityForGivenSku(iterable $itemTransfers, string $sku): Decimal
    {
        $quantity = new Decimal(0);
        foreach ($itemTransfers as $itemTransfer) {
            if ($itemTransfer->getSku() !== $sku) {
                continue;
            }
            $quantity = $quantity->add($itemTransfer->getQuantity());
        }

        return $quantity;
    }

    /**
     * @param \Generated\Shared\Transfer\ItemTransfer $itemTransfer
     *
     * @return bool
     */
    protected function isProductBundle(ItemTransfer $itemTransfer): bool
    {
        if ($itemTransfer->getBundleItemIdentifier() !== null) {
            return true;
        }

        return $this->productBundleQueryContainer
            ->queryBundleProductBySku($itemTransfer->getSku())
            ->exists();
    }
}
