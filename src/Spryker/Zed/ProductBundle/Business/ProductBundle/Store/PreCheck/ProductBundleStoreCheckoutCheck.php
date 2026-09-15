<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ProductBundle\Business\ProductBundle\Store\PreCheck;

use Generated\Shared\Transfer\CheckoutErrorTransfer;
use Generated\Shared\Transfer\CheckoutResponseTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\ProductCriteriaTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Spryker\Zed\ProductBundle\Dependency\Facade\ProductBundleToProductFacadeInterface;
use Spryker\Zed\ProductBundle\Dependency\Facade\ProductBundleToStoreFacadeInterface;
use Spryker\Zed\ProductBundle\Exception\MissingFacadeMethodException;
use Spryker\Zed\ProductBundle\Persistence\ProductBundleRepositoryInterface;
use Spryker\Zed\ProductBundle\ProductBundleConfig;

class ProductBundleStoreCheckoutCheck implements ProductBundleStoreCheckoutCheckInterface
{
    protected const string ERROR_BUNDLE_NOT_ASSIGNED_TRANSLATION_KEY = 'product.unavailable';

    protected const string ERROR_BUNDLED_PRODUCT_NOT_ASSIGNED_TRANSLATION_KEY = 'product_bundle.unavailable';

    protected const string ERROR_PARAMETER_SKU = '%sku%';

    protected const string ERROR_PARAMETER_BUNDLE_SKU = '%bundleSku%';

    protected const string ERROR_PARAMETER_PRODUCT_SKU = '%productSku%';

    public function __construct(
        protected ProductBundleToProductFacadeInterface $productFacade,
        protected ProductBundleToStoreFacadeInterface $storeFacade,
        protected ProductBundleRepositoryInterface $productBundleRepository,
        protected ProductBundleConfig $productBundleConfig
    ) {
    }

    /**
     * @param array<string, string> $skusToSkip
     */
    public function checkCheckoutStoreAssignment(
        QuoteTransfer $quoteTransfer,
        CheckoutResponseTransfer $checkoutResponseTransfer,
        array $skusToSkip = []
    ): bool {
        $bundleItemTransfers = $this->getUniqueBundleItemTransfers($quoteTransfer, $skusToSkip);

        if ($bundleItemTransfers === []) {
            return true;
        }

        $bundleProductSkus = $this->extractBundleProductSkus($bundleItemTransfers);
        $bundledProductSkusGroupedByBundleProductSku = $this->productBundleRepository
            ->getBundledProductSkusGroupedByBundleProductSku($bundleProductSkus);
        // This check is needed for BC reasons, because ProductFacade::getProductConcretesByCriteria() was added in spryker/product:6.35.0.
        try {
            $skusAssignedToStore = $this->getSkusAssignedToStore(
                $this->mergeSkusToCheck($bundleProductSkus, $bundledProductSkusGroupedByBundleProductSku),
                $this->resolveStore($quoteTransfer),
            );
        } catch (MissingFacadeMethodException $e) {
            return true;
        }

        $isSuccessful = true;

        foreach ($bundleItemTransfers as $bundleItemTransfer) {
            $bundledProductSkus = $bundledProductSkusGroupedByBundleProductSku[$bundleItemTransfer->getSkuOrFail()] ?? [];

            if ($this->checkBundleItem($bundleItemTransfer, $bundledProductSkus, $skusAssignedToStore, $checkoutResponseTransfer)) {
                continue;
            }

            $isSuccessful = false;
        }

        if (!$isSuccessful) {
            $checkoutResponseTransfer->setIsSuccess(false);
        }

        return $isSuccessful;
    }

    /**
     * @param list<string> $bundledProductSkus
     * @param array<string, true> $skusAssignedToStore
     */
    protected function checkBundleItem(
        ItemTransfer $bundleItemTransfer,
        array $bundledProductSkus,
        array $skusAssignedToStore,
        CheckoutResponseTransfer $checkoutResponseTransfer
    ): bool {
        if (!isset($skusAssignedToStore[$bundleItemTransfer->getSkuOrFail()])) {
            $checkoutResponseTransfer->addError($this->createBundleNotAssignedCheckoutError($bundleItemTransfer));

            return false;
        }

        $isSuccessful = true;

        foreach ($bundledProductSkus as $bundledProductSku) {
            if (isset($skusAssignedToStore[$bundledProductSku])) {
                continue;
            }

            $checkoutResponseTransfer->addError(
                $this->createBundledProductNotAssignedCheckoutError($bundleItemTransfer, $bundledProductSku),
            );
            $isSuccessful = false;
        }

        return $isSuccessful;
    }

    protected function resolveStore(QuoteTransfer $quoteTransfer): StoreTransfer
    {
        return $this->storeFacade->getStoreByName($quoteTransfer->getStoreOrFail()->getNameOrFail());
    }

    /**
     * @param array<string, string> $skusToSkip
     *
     * @return list<\Generated\Shared\Transfer\ItemTransfer>
     */
    protected function getUniqueBundleItemTransfers(QuoteTransfer $quoteTransfer, array $skusToSkip = []): array
    {
        $uniqueBundleItemTransfers = [];

        foreach ($quoteTransfer->getBundleItems() as $bundleItemTransfer) {
            if (isset($skusToSkip[$bundleItemTransfer->getSkuOrFail()])) {
                continue;
            }

            $uniqueBundleItemTransfers[$bundleItemTransfer->getGroupKey() ?: $bundleItemTransfer->getSkuOrFail()] = $bundleItemTransfer;
        }

        return array_values($uniqueBundleItemTransfers);
    }

    /**
     * @param list<\Generated\Shared\Transfer\ItemTransfer> $bundleItemTransfers
     *
     * @return list<string>
     */
    protected function extractBundleProductSkus(array $bundleItemTransfers): array
    {
        $bundleProductSkus = [];

        foreach ($bundleItemTransfers as $bundleItemTransfer) {
            $bundleProductSkus[$bundleItemTransfer->getSkuOrFail()] = $bundleItemTransfer->getSkuOrFail();
        }

        return array_values($bundleProductSkus);
    }

    /**
     * @param list<string> $bundleProductSkus
     * @param array<string, list<string>> $bundledProductSkusGroupedByBundleProductSku
     *
     * @return list<string>
     */
    protected function mergeSkusToCheck(array $bundleProductSkus, array $bundledProductSkusGroupedByBundleProductSku): array
    {
        $skus = $bundleProductSkus;

        foreach ($bundledProductSkusGroupedByBundleProductSku as $bundledProductSkus) {
            foreach ($bundledProductSkus as $bundledProductSku) {
                $skus[] = $bundledProductSku;
            }
        }

        return array_values(array_unique($skus));
    }

    /**
     * @param list<string> $skus
     *
     * @return array<string, true>
     */
    protected function getSkusAssignedToStore(array $skus, StoreTransfer $storeTransfer): array
    {
        $productCriteriaTransfer = (new ProductCriteriaTransfer())
            ->setSkus($skus)
            ->setIdStore($storeTransfer->getIdStoreOrFail())
            ->setWithoutAdditionalProductData(true);

        $skusAssignedToStore = [];

        foreach ($this->productFacade->getProductConcretesByCriteria($productCriteriaTransfer) as $productConcreteTransfer) {
            $skusAssignedToStore[$productConcreteTransfer->getSkuOrFail()] = true;
        }

        return $skusAssignedToStore;
    }

    protected function createBundleNotAssignedCheckoutError(ItemTransfer $bundleItemTransfer): CheckoutErrorTransfer
    {
        return (new CheckoutErrorTransfer())
            ->setMessage(static::ERROR_BUNDLE_NOT_ASSIGNED_TRANSLATION_KEY)
            ->setParameters([static::ERROR_PARAMETER_SKU => $bundleItemTransfer->getSkuOrFail()])
            ->setErrorType($this->productBundleConfig->getCheckoutErrorType())
            ->setGroupKey($bundleItemTransfer->getGroupKey());
    }

    protected function createBundledProductNotAssignedCheckoutError(
        ItemTransfer $bundleItemTransfer,
        string $bundledProductSku
    ): CheckoutErrorTransfer {
        return (new CheckoutErrorTransfer())
            ->setMessage(static::ERROR_BUNDLED_PRODUCT_NOT_ASSIGNED_TRANSLATION_KEY)
            ->setParameters([
                static::ERROR_PARAMETER_BUNDLE_SKU => $bundleItemTransfer->getSkuOrFail(),
                static::ERROR_PARAMETER_PRODUCT_SKU => $bundledProductSku,
            ])
            ->setErrorType($this->productBundleConfig->getCheckoutErrorType())
            ->setGroupKey($bundleItemTransfer->getGroupKey());
    }
}
