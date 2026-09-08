<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ProductBundle\Business\ProductBundle;

use ArrayObject;
use Generated\Shared\Transfer\ProductConcreteConditionsTransfer;
use Generated\Shared\Transfer\ProductConcreteCriteriaTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;
use Generated\Shared\Transfer\ProductForBundleTransfer;
use Spryker\Zed\ProductBundle\Business\ProductBundle\Stock\ProductBundleStockWriterInterface;
use Spryker\Zed\ProductBundle\Dependency\Facade\ProductBundleToProductFacadeInterface;
use Spryker\Zed\ProductBundle\Persistence\ProductBundleQueryContainerInterface;
use Throwable;

class ProductBundleWriter implements ProductBundleWriterInterface
{
    /**
     * @var \Spryker\Zed\ProductBundle\Persistence\ProductBundleQueryContainerInterface
     */
    protected $productBundleQueryContainer;

    /**
     * @var \Spryker\Zed\ProductBundle\Business\ProductBundle\Stock\ProductBundleStockWriterInterface
     */
    protected $productBundleStockWriter;

    protected ProductBundleToProductFacadeInterface $productFacade;

    public function __construct(
        ProductBundleQueryContainerInterface $productBundleQueryContainer,
        ProductBundleStockWriterInterface $productBundleStockWriter,
        ProductBundleToProductFacadeInterface $productFacade,
    ) {
        $this->productBundleQueryContainer = $productBundleQueryContainer;
        $this->productBundleStockWriter = $productBundleStockWriter;
        $this->productFacade = $productFacade;
    }

    /**
     * @param \Generated\Shared\Transfer\ProductConcreteTransfer $productConcreteTransfer
     *
     * @throws \Exception
     * @throws \Throwable
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

        if ($bundledProducts->count() == 0 && count($productBundleTransfer->getBundlesToRemove()) == 0) {
            return $productConcreteTransfer;
        }

        $productConcreteTransfer->requireIdProductConcrete();

        $this->resolveBundledProductIds($bundledProducts);

        try {
            $this->productBundleQueryContainer->getConnection()->beginTransaction();

            $this->createBundledProducts($productConcreteTransfer, $bundledProducts);
            $this->removeBundledProducts($productBundleTransfer->getBundlesToRemove(), $productConcreteTransfer->getIdProductConcrete());

            $this->productBundleQueryContainer->getConnection()->commit();
        } catch (Throwable $exception) {
            $this->productBundleQueryContainer->getConnection()->rollBack();

            throw $exception;
        }
        $productBundleTransfer->setBundlesToRemove([]);

        $this->productBundleStockWriter->updateStock($productConcreteTransfer);

        return $productConcreteTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\ProductConcreteTransfer $productConcreteTransfer
     * @param \ArrayObject<int, \Generated\Shared\Transfer\ProductForBundleTransfer> $bundledProducts
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
     * @param \ArrayObject<int, \Generated\Shared\Transfer\ProductForBundleTransfer> $bundledProducts
     */
    protected function resolveBundledProductIds(ArrayObject $bundledProducts): void
    {
        $skusToResolve = [];

        foreach ($bundledProducts as $productForBundleTransfer) {
            if ($productForBundleTransfer->getIdProductConcrete() === null && $productForBundleTransfer->getSku() !== null) {
                $skusToResolve[$productForBundleTransfer->getSku()] = true;
            }
        }

        if ($skusToResolve === []) {
            return;
        }

        $criteriaTransfer = (new ProductConcreteCriteriaTransfer())
            ->setProductConcreteConditions(
                (new ProductConcreteConditionsTransfer())->setSkus(array_keys($skusToResolve)),
            );

        $collectionTransfer = $this->productFacade->getProductConcreteCollection($criteriaTransfer);

        $productIdsIndexedBySku = [];

        foreach ($collectionTransfer->getProducts() as $productConcreteTransfer) {
            $productIdsIndexedBySku[$productConcreteTransfer->getSkuOrFail()] = $productConcreteTransfer->getIdProductConcreteOrFail();
        }

        foreach ($bundledProducts as $productForBundleTransfer) {
            if ($productForBundleTransfer->getIdProductConcrete() !== null) {
                continue;
            }

            $sku = $productForBundleTransfer->getSku();

            if ($sku !== null && isset($productIdsIndexedBySku[$sku])) {
                $productForBundleTransfer->setIdProductConcrete($productIdsIndexedBySku[$sku]);
            }
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
     * @return \Orm\Zed\ProductBundle\Persistence\SpyProductBundle|null
     */
    protected function findProductBundleEntity($idProductBundle, $idBundledProduct)
    {
        return $this->productBundleQueryContainer
            ->queryBundledProductByIdProduct($idBundledProduct)
            ->filterByFkProduct($idProductBundle)
            ->findOne();
    }
}
