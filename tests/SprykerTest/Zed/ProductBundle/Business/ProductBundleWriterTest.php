<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\ProductBundle\Business;

use ArrayObject;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\ProductBundleTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;
use Generated\Shared\Transfer\ProductForBundleTransfer;
use Orm\Zed\ProductBundle\Persistence\SpyProductBundle;
use Propel\Runtime\Connection\ConnectionInterface;
use Spryker\Zed\ProductBundle\Business\ProductBundle\ProductBundleWriter;
use Spryker\Zed\ProductBundle\Business\ProductBundle\Stock\ProductBundleStockWriterInterface;
use Spryker\Zed\ProductBundle\Persistence\ProductBundleQueryContainerInterface;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group ProductBundle
 * @group Business
 * @group ProductBundleWriterTest
 * Add your own group annotations below this line
 */
class ProductBundleWriterTest extends Unit
{
    /**
     * @return void
     */
    public function testSaveBundledProductsShouldPersistGivenBundles(): void
    {
        $idBundledProductToRemove = 3;
        $idProductBundle = 1;
        $idBundledProductToAdd = 2;
        $quantityBundledProductToAdd = 2;

        $productBundleStockWriterMock = $this->createProductBundleStockWriter();

        $productBundleStockWriterMock->expects($this->once())
            ->method('updateStock');

        $productBundleWriterMock = $this->createProductBundleWriterMock($productBundleStockWriterMock);

        $productConcreteTransfer = new ProductConcreteTransfer();
        $productConcreteTransfer->setIdProductConcrete($idProductBundle);

        $productBundleTransfer = new ProductBundleTransfer();

        $bundledProducts = new ArrayObject();
        $productForBundleTransfer = new ProductForBundleTransfer();
        $productForBundleTransfer->setIdProductConcrete($idBundledProductToAdd);
        $productForBundleTransfer->setQuantity($quantityBundledProductToAdd);
        $bundledProducts->append($productForBundleTransfer);

        $productBundleTransfer->setBundledProducts($bundledProducts);

        $productBundleTransfer->setBundlesToRemove([$idBundledProductToRemove]);

        $productConcreteTransfer->setProductBundle($productBundleTransfer);

        $productBundleEntityMock = $this->createProductBundleEntityMock();

        $productBundleWriterMock->expects($this->once())
            ->method('findOrCreateProductBundleEntity')
            ->with($productForBundleTransfer, $idProductBundle)
            ->willReturn($productBundleEntityMock);

        $productBundleEntityMockForDelete = $this->createProductBundleEntityMock();
        $productBundleWriterMock->expects($this->once())
            ->method('findProductBundleEntity')
            ->with($idProductBundle, $idBundledProductToRemove)
            ->willReturn($productBundleEntityMockForDelete);

        $productConcreteTransfer = $productBundleWriterMock->saveBundledProducts($productConcreteTransfer);

        $savedProductBundleTransfer = $productConcreteTransfer->getProductBundle();

        $this->assertNotNull($savedProductBundleTransfer);
        $this->assertCount(1, $productBundleTransfer->getBundledProducts());

        $savedProductForBundleTransfer = $productBundleTransfer->getBundledProducts()[0];
        $this->assertNotNull($savedProductForBundleTransfer->getIdProductBundle());
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Orm\Zed\ProductBundle\Persistence\SpyProductBundle
     */
    protected function createProductBundleEntityMock(): SpyProductBundle
    {
        $productBundleEntityMock = $this->getMockBuilder(SpyProductBundle::class)
            ->onlyMethods(['save', 'delete', 'getIdProductBundle'])
            ->getMock();

        $productBundleEntityMock->method('save')->willReturn(1);
        $productBundleEntityMock->method('delete');
        $productBundleEntityMock->method('getIdProductBundle')->willReturn(rand(1, 99));

        return $productBundleEntityMock;
    }

    /**
     * @param \Spryker\Zed\ProductBundle\Business\ProductBundle\Stock\ProductBundleStockWriterInterface|null $productBundleStockWriterMock
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\ProductBundle\Business\ProductBundle\ProductBundleWriter
     */
    protected function createProductBundleWriterMock(?ProductBundleStockWriterInterface $productBundleStockWriterMock = null): ProductBundleWriter
    {
        $productBundleQueryContainerMock = $this->createProductBundleQueryContainerMock();

        $connectionMock = $this->getMockBuilder(ConnectionInterface::class)->getMock();

        $productBundleQueryContainerMock->method('getConnection')->willReturn($connectionMock);

        if ($productBundleStockWriterMock === null) {
            $productBundleStockWriterMock = $this->createProductBundleStockWriter();
        }

        $productBundleStockWriterMock->expects($this->once())->method('updateStock');

        $productBundleWriterMock = $this->getMockBuilder(ProductBundleWriter::class)
            ->setConstructorArgs([$productBundleQueryContainerMock, $productBundleStockWriterMock])
            ->onlyMethods(['findOrCreateProductBundleEntity', 'findProductBundleEntity'])
            ->getMock();

        return $productBundleWriterMock;
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Business\ProductBundle\Stock\ProductBundleStockWriterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createProductBundleStockWriter(): ProductBundleStockWriterInterface
    {
        return $this->getMockBuilder(ProductBundleStockWriterInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\ProductBundle\Persistence\ProductBundleQueryContainerInterface
     */
    protected function createProductBundleQueryContainerMock(): ProductBundleQueryContainerInterface
    {
        return $this->getMockBuilder(ProductBundleQueryContainerInterface::class)->getMock();
    }
}
