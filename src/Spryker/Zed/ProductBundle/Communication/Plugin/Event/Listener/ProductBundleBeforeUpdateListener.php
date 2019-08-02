<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ProductBundle\Communication\Plugin\Event\Listener;

use Generated\Shared\Transfer\ProductBundleCriteriaFilterTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;
use Spryker\Shared\Kernel\Transfer\TransferInterface;
use Spryker\Zed\Event\Dependency\Plugin\EventHandlerInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * @method \Spryker\Zed\ProductBundle\Business\ProductBundleFacadeInterface getFacade()
 * @method \Spryker\Zed\ProductBundle\Communication\ProductBundleCommunicationFactory getFactory()
 * @method \Spryker\Zed\ProductBundle\ProductBundleConfig getConfig()
 * @method \Spryker\Zed\ProductBundle\Persistence\ProductBundleQueryContainerInterface getQueryContainer()
 */
class ProductBundleBeforeUpdateListener extends AbstractPlugin implements EventHandlerInterface
{
    /**
     * Specification
     * - Sets `isActive` to false if all bundled products wasn't active, if the product is a product bundle.
     * - Updates bundle availability, if the product is a bundled product, only if `isActive` was modified.
     *
     * @api
     *
     * @param \Spryker\Shared\Kernel\Transfer\TransferInterface $productConcreteTransfer
     * @param string $eventName
     *
     * @return void
     */
    public function handle(TransferInterface $productConcreteTransfer, $eventName): void
    {
        if (!$productConcreteTransfer instanceof ProductConcreteTransfer) {
            return;
        }

        $this->deactivateProductBundleIfAllBundledProductsAreDeactivated($productConcreteTransfer);
        $this->updateBundleAvailability($productConcreteTransfer);
    }

    /**
     * @param \Generated\Shared\Transfer\ProductConcreteTransfer $productConcreteTransfer
     *
     * @return void
     */
    protected function deactivateProductBundleIfAllBundledProductsAreDeactivated(ProductConcreteTransfer $productConcreteTransfer): void
    {
        if ($productConcreteTransfer->getProductBundle() === null) {
            return;
        }

        $bundledProducts = $this->getFacade()
            ->findBundledProductsByIdProductConcrete(
                $productConcreteTransfer->getIdProductConcrete()
            );

        $AllInactive = true;
        foreach ($bundledProducts as $forBundleTransfer) {
            $AllInactive = !$forBundleTransfer->getIsActive() && $AllInactive;
        }

        if ($AllInactive) {
            $productConcreteTransfer->setIsActive(false);
        }
    }

    /**
     * @param \Generated\Shared\Transfer\ProductConcreteTransfer $productConcreteTransfer
     *
     * @return void
     */
    protected function updateBundleAvailability(ProductConcreteTransfer $productConcreteTransfer): void
    {
        if ($productConcreteTransfer->getProductBundle() !== null) {
            return;
        }

        if (!$productConcreteTransfer->isPropertyModified(ProductConcreteTransfer::IS_ACTIVE)) {
            return;
        }

        $productBundleCollectionTransfer = $this->getFacade()
            ->getProductBundleCollectionByCriteriaFilter(
                (new ProductBundleCriteriaFilterTransfer())->setIdBundledProduct($productConcreteTransfer->getIdProductConcrete())
            );

        foreach ($productBundleCollectionTransfer->getProductBundles() as $productBundleTransfer) {
            $this->getFacade()->updateBundleAvailability($productBundleTransfer->getSkuProductConcreteBundle());
        }
    }
}
