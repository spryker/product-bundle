<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Zed\ProductBundle\Business\Product\Validator;

use Generated\Shared\Transfer\ErrorTransfer;
use Generated\Shared\Transfer\ProductConcreteCollectionRequestTransfer;
use Generated\Shared\Transfer\ProductConcreteCollectionResponseTransfer;
use Spryker\Zed\ProductBundle\Dependency\Facade\ProductBundleToProductFacadeInterface;

class ProductBundleValidator implements ProductBundleValidatorInterface
{
    public function __construct(
        protected readonly ProductBundleToProductFacadeInterface $productFacade,
    ) {
    }

    public function validateProductConcreteCollection(
        ProductConcreteCollectionRequestTransfer $productConcreteCollectionRequestTransfer,
        ProductConcreteCollectionResponseTransfer $productConcreteCollectionResponseTransfer
    ): ProductConcreteCollectionResponseTransfer {
        $bundledSkusByBundleSku = $this->extractBundledSkusByBundleSku($productConcreteCollectionRequestTransfer);

        if ($bundledSkusByBundleSku === []) {
            return $productConcreteCollectionResponseTransfer;
        }

        $knownSkus = $this->getKnownSkus($bundledSkusByBundleSku);

        foreach ($bundledSkusByBundleSku as $bundleSku => $bundledSkus) {
            foreach ($bundledSkus as $bundledSku) {
                $this->validateBundledSku(
                    (string)$bundleSku,
                    $bundledSku,
                    $knownSkus,
                    $productConcreteCollectionResponseTransfer,
                );
            }
        }

        return $productConcreteCollectionResponseTransfer;
    }

    /**
     * @param list<string> $knownSkus
     */
    protected function validateBundledSku(
        string $bundleSku,
        string $bundledSku,
        array $knownSkus,
        ProductConcreteCollectionResponseTransfer $productConcreteCollectionResponseTransfer
    ): void {
        if ($bundledSku === $bundleSku) {
            $productConcreteCollectionResponseTransfer->addError(
                $this->createError($bundleSku, sprintf('Product bundle "%s" must not contain itself.', $bundleSku)),
            );

            return;
        }

        if (!in_array($bundledSku, $knownSkus, true)) {
            $productConcreteCollectionResponseTransfer->addError(
                $this->createError(
                    $bundleSku,
                    sprintf('Bundled concrete product with SKU "%s" does not exist.', $bundledSku),
                ),
            );
        }
    }

    /**
     * @return array<string, list<string>>
     */
    protected function extractBundledSkusByBundleSku(
        ProductConcreteCollectionRequestTransfer $productConcreteCollectionRequestTransfer
    ): array {
        $bundledSkusByBundleSku = [];

        foreach ($productConcreteCollectionRequestTransfer->getProducts() as $productConcreteTransfer) {
            $productBundleTransfer = $productConcreteTransfer->getProductBundle();

            if ($productBundleTransfer === null) {
                continue;
            }

            $bundledSkus = [];

            foreach ($productBundleTransfer->getBundledProducts() as $productForBundleTransfer) {
                $bundledSku = $productForBundleTransfer->getSku();

                if ($bundledSku !== null && $bundledSku !== '') {
                    $bundledSkus[] = $bundledSku;
                }
            }

            if ($bundledSkus !== []) {
                $bundledSkusByBundleSku[(string)$productConcreteTransfer->getSku()] = array_values(array_unique($bundledSkus));
            }
        }

        return $bundledSkusByBundleSku;
    }

    /**
     * @param array<string, list<string>> $bundledSkusByBundleSku
     *
     * @return list<string>
     */
    protected function getKnownSkus(array $bundledSkusByBundleSku): array
    {
        $requestedSkus = array_values(array_unique(array_merge(...array_values($bundledSkusByBundleSku))));

        $knownSkus = [];

        foreach ($requestedSkus as $requestedSku) {
            if ($this->productFacade->hasProductConcrete($requestedSku)) {
                $knownSkus[] = $requestedSku;
            }
        }

        return $knownSkus;
    }

    protected function createError(?string $entityIdentifier, string $message): ErrorTransfer
    {
        return (new ErrorTransfer())
            ->setEntityIdentifier($entityIdentifier)
            ->setMessage($message);
    }
}
