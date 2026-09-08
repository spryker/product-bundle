<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\ProductBundle\Business\ProductBundleFacade;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ProductBundleTransfer;
use Generated\Shared\Transfer\ProductConcreteCollectionTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;
use Generated\Shared\Transfer\ProductForBundleTransfer;
use Orm\Zed\ProductBundle\Persistence\SpyProductBundle;
use Propel\Runtime\Connection\ConnectionInterface;
use Spryker\Zed\ProductBundle\Business\ProductBundle\ProductBundleWriter;
use Spryker\Zed\ProductBundle\Business\ProductBundle\Stock\ProductBundleStockWriterInterface;
use Spryker\Zed\ProductBundle\Dependency\Facade\ProductBundleToProductFacadeInterface;
use Spryker\Zed\ProductBundle\Persistence\ProductBundleQueryContainerInterface;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group ProductBundle
 * @group Business
 * @group ProductBundleFacade
 * @group SaveBundledProductsWithSkuTest
 * Add your own group annotations below this line
 */
class SaveBundledProductsWithSkuTest extends Unit
{
    protected const int ID_PRODUCT_BUNDLE = 100;

    protected const int ID_BUNDLED_PRODUCT = 200;

    protected const string SKU_BUNDLED_PRODUCT = 'bundled-sku-test';

    protected const int BUNDLED_PRODUCT_QUANTITY = 2;

    protected const int ID_PRODUCT_BUNDLE_ENTITY = 999;

    public function testSaveBundledProductsResolvesIdProductConcreteFromSku(): void
    {
        // Arrange
        $productFacadeMock = $this->createProductFacadeMockReturningProduct(
            static::SKU_BUNDLED_PRODUCT,
            static::ID_BUNDLED_PRODUCT,
        );

        $productBundleWriterMock = $this->createProductBundleWriterMock($productFacadeMock);
        $productConcreteTransfer = $this->createBundleProductConcreteWithSkuOnly(static::SKU_BUNDLED_PRODUCT);

        $productBundleEntityMock = $this->createProductBundleEntityMock();
        $productBundleWriterMock->expects($this->once())
            ->method('findOrCreateProductBundleEntity')
            ->willReturn($productBundleEntityMock);

        $productBundleWriterMock->method('findProductBundleEntity')->willReturn(null);

        // Act
        $resultTransfer = $productBundleWriterMock->saveBundledProducts($productConcreteTransfer);

        // Assert
        $resolvedProductForBundleTransfer = $resultTransfer
            ->getProductBundle()
            ->getBundledProducts()
            ->getIterator()
            ->current();

        $this->assertSame(static::ID_BUNDLED_PRODUCT, $resolvedProductForBundleTransfer->getIdProductConcrete());
    }

    public function testSaveBundledProductsWorksWithIdProductConcreteAlreadySet(): void
    {
        // Arrange
        $productFacadeMock = $this->createMock(ProductBundleToProductFacadeInterface::class);
        $productFacadeMock->expects($this->never())->method('getProductConcreteCollection');

        $productBundleWriterMock = $this->createProductBundleWriterMock($productFacadeMock);
        $productConcreteTransfer = $this->createBundleProductConcreteWithId(static::ID_BUNDLED_PRODUCT);

        $productBundleEntityMock = $this->createProductBundleEntityMock();
        $productBundleWriterMock->expects($this->once())
            ->method('findOrCreateProductBundleEntity')
            ->willReturn($productBundleEntityMock);

        $productBundleWriterMock->method('findProductBundleEntity')->willReturn(null);

        // Act
        $resultTransfer = $productBundleWriterMock->saveBundledProducts($productConcreteTransfer);

        // Assert
        $resolvedProductForBundleTransfer = $resultTransfer
            ->getProductBundle()
            ->getBundledProducts()
            ->getIterator()
            ->current();

        $this->assertSame(static::ID_BUNDLED_PRODUCT, $resolvedProductForBundleTransfer->getIdProductConcrete());
    }

    public function testSaveBundledProductsLeavesIdNullWhenSkuNotFound(): void
    {
        // Arrange
        $productFacadeMock = $this->createProductFacadeMockReturningEmpty();
        $productBundleWriterMock = $this->createProductBundleWriterMock($productFacadeMock);
        $productConcreteTransfer = $this->createBundleProductConcreteWithSkuOnly('non-existing-sku');

        $productBundleWriterMock->method('findOrCreateProductBundleEntity')
            ->willReturn($this->createProductBundleEntityMock());
        $productBundleWriterMock->method('findProductBundleEntity')->willReturn(null);

        $resultTransfer = $productBundleWriterMock->saveBundledProducts($productConcreteTransfer);

        $resolvedProductForBundleTransfer = $resultTransfer
            ->getProductBundle()
            ->getBundledProducts()
            ->getIterator()
            ->current();

        $this->assertNull($resolvedProductForBundleTransfer->getIdProductConcrete());
    }

    public function testSaveBundledProductsSkipsResolutionWhenAllIdsAreSet(): void
    {
        // Arrange
        $productFacadeMock = $this->createMock(ProductBundleToProductFacadeInterface::class);
        $productFacadeMock->expects($this->never())->method('getProductConcreteCollection');

        $productBundleWriterMock = $this->createProductBundleWriterMock($productFacadeMock);

        $firstProductForBundleTransfer = (new ProductForBundleTransfer())
            ->setIdProductConcrete(static::ID_BUNDLED_PRODUCT)
            ->setQuantity(static::BUNDLED_PRODUCT_QUANTITY);

        $secondProductForBundleTransfer = (new ProductForBundleTransfer())
            ->setIdProductConcrete(static::ID_BUNDLED_PRODUCT + 1)
            ->setQuantity(static::BUNDLED_PRODUCT_QUANTITY);

        $productConcreteTransfer = (new ProductConcreteTransfer())
            ->setIdProductConcrete(static::ID_PRODUCT_BUNDLE)
            ->setProductBundle(
                (new ProductBundleTransfer())
                    ->addBundledProduct($firstProductForBundleTransfer)
                    ->addBundledProduct($secondProductForBundleTransfer),
            );

        $productBundleEntityMock = $this->createProductBundleEntityMock();
        $productBundleWriterMock->method('findOrCreateProductBundleEntity')
            ->willReturn($productBundleEntityMock);
        $productBundleWriterMock->method('findProductBundleEntity')->willReturn(null);

        // Act
        $resultTransfer = $productBundleWriterMock->saveBundledProducts($productConcreteTransfer);

        // Assert
        $bundledProducts = $resultTransfer->getProductBundle()->getBundledProducts();
        $this->assertCount(2, $bundledProducts);
        $this->assertSame(static::ID_BUNDLED_PRODUCT, $bundledProducts[0]->getIdProductConcrete());
        $this->assertSame(static::ID_BUNDLED_PRODUCT + 1, $bundledProducts[1]->getIdProductConcrete());
    }

    protected function createBundleProductConcreteWithSkuOnly(string $sku): ProductConcreteTransfer
    {
        $productForBundleTransfer = (new ProductForBundleTransfer())
            ->setSku($sku)
            ->setQuantity(static::BUNDLED_PRODUCT_QUANTITY);

        return (new ProductConcreteTransfer())
            ->setIdProductConcrete(static::ID_PRODUCT_BUNDLE)
            ->setProductBundle(
                (new ProductBundleTransfer())
                    ->addBundledProduct($productForBundleTransfer),
            );
    }

    protected function createBundleProductConcreteWithId(int $idProductConcrete): ProductConcreteTransfer
    {
        $productForBundleTransfer = (new ProductForBundleTransfer())
            ->setIdProductConcrete($idProductConcrete)
            ->setQuantity(static::BUNDLED_PRODUCT_QUANTITY);

        return (new ProductConcreteTransfer())
            ->setIdProductConcrete(static::ID_PRODUCT_BUNDLE)
            ->setProductBundle(
                (new ProductBundleTransfer())
                    ->addBundledProduct($productForBundleTransfer),
            );
    }

    protected function createProductFacadeMockReturningProduct(string $sku, int $idProductConcrete): ProductBundleToProductFacadeInterface
    {
        $collectionTransfer = (new ProductConcreteCollectionTransfer())
            ->addProduct(
                (new ProductConcreteTransfer())
                    ->setSku($sku)
                    ->setIdProductConcrete($idProductConcrete),
            );

        $mock = $this->createMock(ProductBundleToProductFacadeInterface::class);
        $mock->method('getProductConcreteCollection')->willReturn($collectionTransfer);

        return $mock;
    }

    protected function createProductFacadeMockReturningEmpty(): ProductBundleToProductFacadeInterface
    {
        $mock = $this->createMock(ProductBundleToProductFacadeInterface::class);
        $mock->method('getProductConcreteCollection')->willReturn(new ProductConcreteCollectionTransfer());

        return $mock;
    }

    protected function createProductBundleWriterMock(ProductBundleToProductFacadeInterface $productFacadeMock): ProductBundleWriter
    {
        $queryContainerMock = $this->createMock(ProductBundleQueryContainerInterface::class);
        $connectionMock = $this->createMock(ConnectionInterface::class);
        $queryContainerMock->method('getConnection')->willReturn($connectionMock);

        $stockWriterMock = $this->createMock(ProductBundleStockWriterInterface::class);
        $stockWriterMock->method('updateStock');

        return $this->getMockBuilder(ProductBundleWriter::class)
            ->setConstructorArgs([$queryContainerMock, $stockWriterMock, $productFacadeMock])
            ->onlyMethods(['findOrCreateProductBundleEntity', 'findProductBundleEntity'])
            ->getMock();
    }

    protected function createProductBundleEntityMock(): SpyProductBundle
    {
        $mock = $this->getMockBuilder(SpyProductBundle::class)
            ->onlyMethods(['save', 'delete', 'getIdProductBundle'])
            ->getMock();

        $mock->method('save')->willReturn(1);
        $mock->method('getIdProductBundle')->willReturn(static::ID_PRODUCT_BUNDLE_ENTITY);

        return $mock;
    }
}
