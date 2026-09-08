<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Zed\ProductBundle\Communication\Plugin\Product;

use Generated\Shared\Transfer\ProductConcreteCollectionRequestTransfer;
use Generated\Shared\Transfer\ProductConcreteCollectionResponseTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\ProductExtension\Dependency\Plugin\ProductConcreteCollectionUpdateValidatorPluginInterface;

/**
 * @method \Spryker\Zed\ProductBundle\Business\ProductBundleBusinessFactory getBusinessFactory()
 */
class ProductBundleProductConcreteCollectionUpdateValidatorPlugin extends AbstractPlugin implements ProductConcreteCollectionUpdateValidatorPluginInterface
{
    /**
     * {@inheritDoc}
     * - Validates the bundled products referenced in `ProductConcreteTransfer.productBundle.bundledProducts`.
     * - Adds an error when a bundle references itself.
     * - Resolves all referenced bundled SKUs of the collection with a single query.
     * - Adds an error per bundle referencing a concrete product SKU that does not exist.
     * - Returns the response unchanged when no concrete product carries a product bundle.
     *
     * @api
     */
    public function validate(
        ProductConcreteCollectionRequestTransfer $productConcreteCollectionRequestTransfer,
        ProductConcreteCollectionResponseTransfer $productConcreteCollectionResponseTransfer
    ): ProductConcreteCollectionResponseTransfer {
        return $this->getBusinessFactory()
            ->createProductBundleValidator()
            ->validateProductConcreteCollection($productConcreteCollectionRequestTransfer, $productConcreteCollectionResponseTransfer);
    }
}
