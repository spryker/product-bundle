<?php

/**
 * Copyright © 2017-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ProductBundle\Business\ProductBundle;

use ArrayObject;
use Generated\Shared\Transfer\ProductConcreteTransfer;
use Generated\Shared\Transfer\ProductForBundleTransfer;
use Spryker\Zed\ProductBundle\Business\ProductBundle\Stock\ProductBundleStockWriterInterface;
use Spryker\Zed\ProductBundle\Dependency\Facade\ProductBundleToProductInterface;
use Spryker\Zed\ProductBundle\Persistence\ProductBundleQueryContainerInterface;

class ProductBundleWriter implements ProductBundleWriterInterface
{

    /**
     * @var \Spryker\Zed\ProductBundle\Dependency\Facade\ProductBundleToProductInterface
     */
    protected $productFacade;

    /**
     * @var \Spryker\Zed\ProductBundle\Persistence\ProductBundleQueryContainerInterface
     */
    protected $productBundleQueryContainer;

    /**
     * @var \Spryker\Zed\ProductBundle\Business\ProductBundle\Stock\ProductBundleStockWriter
     */
    protected $productBundleStockWriter;

    /**
     * @param \Spryker\Zed\ProductBundle\Dependency\Facade\ProductBundleToProductInterface $productFacade
     * @param \Spryker\Zed\ProductBundle\Persistence\ProductBundleQueryContainerInterface $productBundleQueryContainer
     * @param \Spryker\Zed\ProductBundle\Business\ProductBundle\Stock\ProductBundleStockWriterInterface $productBundleStockWriter
     */
    public function __construct(
        ProductBundleToProductInterface $productFacade,
        ProductBundleQueryContainerInterface $productBundleQueryContainer,
        ProductBundleStockWriterInterface $productBundleStockWriter
    ) {
        $this->productFacade = $productFacade;
        $this->productBundleQueryContainer = $productBundleQueryContainer;
        $this->productBundleStockWriter = $productBundleStockWriter;
    }

    /**
     * @param \Generated\Shared\Transfer\ProductConcreteTransfer $productConcreteTransfer
     *
     * @return \Generated\Shared\Transfer\ProductConcreteTransfer
     */
    public function saveBundledProducts(ProductConcreteTransfer $productConcreteTransfer)
    {
        if ($productConcreteTransfer->getProductBundle() === null) {
            return $productConcreteTransfer;
        }

        $productBundleTransfer = $productConcreteTransfer->getProductBundle();
        $bundledProducts = $productBundleTransfer->getBundledProducts();

        if ($bundledProducts->count() == 0) {
            return $productConcreteTransfer;
        }

        $productConcreteTransfer->requireIdProductConcrete();

        $this->createBundledProducts($productConcreteTransfer, $bundledProducts);
        $this->removeBundledProducts($productBundleTransfer->getBundlesToRemove(), $productConcreteTransfer->getIdProductConcrete());
        $productBundleTransfer->setBundlesToRemove([]);

        $this->productBundleStockWriter->updateStock($productConcreteTransfer);

        return $productConcreteTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\ProductConcreteTransfer $productConcreteTransfer
     * @param \ArrayObject|\Generated\Shared\Transfer\ProductForBundleTransfer[] $bundledProducts
     *
     * @return void
     */
    protected function createBundledProducts(ProductConcreteTransfer $productConcreteTransfer, ArrayObject $bundledProducts)
    {
        foreach ($bundledProducts as $productForBundleTransfer) {
            $this->createProductBundleEntity($productForBundleTransfer, $productConcreteTransfer->getIdProductConcrete());
        }
    }

    /**
     * @param \Generated\Shared\Transfer\ProductForBundleTransfer $productForBundleTransfer
     * @param int $idProductBundle
     *
     * @return void
     */
    protected function createProductBundleEntity(ProductForBundleTransfer $productForBundleTransfer, $idProductBundle)
    {
        $productBundleEntity = $this->findOrCreateProductBundleEntity($productForBundleTransfer, $idProductBundle);
        $productBundleEntity->setQuantity($productForBundleTransfer->getQuantity());
        $productBundleEntity->save();

        $productForBundleTransfer->setIdProductBundle($productBundleEntity->getIdProductBundle());
    }

    /**
     * @param array $productsToRemove
     * @param int $idProductBundle
     *
     * @return void
     */
    protected function removeBundledProducts(array $productsToRemove, $idProductBundle)
    {
        foreach ($productsToRemove as $idBundledProduct) {
            $productBundleEntity = $this->findProductBundleEntity($idProductBundle, $idBundledProduct);

            if ($productBundleEntity === null) {
                continue;
            }

            $productBundleEntity->delete();
        }
    }

    /**
     * @param \Generated\Shared\Transfer\ProductForBundleTransfer $productForBundleTransfer
     * @param int $idProductBundle
     *
     * @return \Orm\Zed\ProductBundle\Persistence\SpyProductBundle
     */
    protected function findOrCreateProductBundleEntity(ProductForBundleTransfer $productForBundleTransfer, $idProductBundle)
    {
        $productForBundleTransfer->requireIdProductConcrete();

        return $this->productBundleQueryContainer
            ->queryBundleProduct($idProductBundle)
            ->filterByFkBundledProduct($productForBundleTransfer->getIdProductConcrete())
            ->findOneOrCreate();
    }

    /**
     * @param int $idProductBundle
     * @param int $idBundledProduct
     *
     * @return \Orm\Zed\ProductBundle\Persistence\SpyProductBundle
     */
    protected function findProductBundleEntity($idProductBundle, $idBundledProduct)
    {
        return $this->productBundleQueryContainer
            ->queryBundledProductByIdProduct($idBundledProduct)
            ->filterByFkProduct($idProductBundle)
            ->findOne();
    }

}
