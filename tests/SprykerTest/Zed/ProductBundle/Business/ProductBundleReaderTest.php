<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\ProductBundle\Business;

use Codeception\Test\Unit;
use Generated\Shared\DataBuilder\StoreBuilder;
use Generated\Shared\Transfer\ProductConcreteAvailabilityTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Orm\Zed\Product\Persistence\SpyProduct;
use Orm\Zed\ProductBundle\Persistence\SpyProductBundle;
use PHPUnit\Framework\MockObject\MockObject;
use Spryker\DecimalObject\Decimal;
use Spryker\Zed\ProductBundle\Business\ProductBundle\ProductBundleReader;
use Spryker\Zed\ProductBundle\Dependency\Facade\ProductBundleToAvailabilityFacadeInterface;
use Spryker\Zed\ProductBundle\Dependency\Facade\ProductBundleToStoreFacadeInterface;
use Spryker\Zed\ProductBundle\Persistence\ProductBundleQueryContainerInterface;
use Spryker\Zed\ProductBundle\Persistence\ProductBundleRepositoryInterface;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group ProductBundle
 * @group Business
 * @group ProductBundleReaderTest
 * Add your own group annotations below this line
 */
class ProductBundleReaderTest extends Unit
{
    public const ID_STORE = 1;
    /**
     * @var array
     */
    protected $fixtures = [
        'idProductConcrete' => 1,
        'bundledProductSku' => 'sku-123',
        'fkBundledProduct' => 2,
        'bundledProductQuantity' => 5,
        'idProductBundle' => 1,
    ];

    /**
     * @return void
     */
    public function testFindBundledProductsByIdProductConcreteShouldBuildTransferCollectionFromPersistence(): void
    {
        $productBundleRepositoryMock = $this->createProductBundleRepositoryMock();
        $this->setupFindBundledProducts($this->fixtures, $productBundleRepositoryMock);
        $productBundleReaderMock = $this->createProductBundleReader($productBundleRepositoryMock);

        $bundledProductsTransferCollection = $productBundleReaderMock->findBundledProductsByIdProductConcrete($this->fixtures['idProductConcrete']);

        $productForBundleTransfer = $bundledProductsTransferCollection[0];
        $this->assertSame($this->fixtures['bundledProductSku'], $productForBundleTransfer->getSku());
        $this->assertSame($this->fixtures['fkBundledProduct'], $productForBundleTransfer->getIdProductConcrete());
        $this->assertTrue((new Decimal($this->fixtures['bundledProductQuantity']))->equals($productForBundleTransfer->getQuantity()));
        $this->assertSame($this->fixtures['idProductBundle'], $productForBundleTransfer->getIdProductBundle());
    }

    /**
     * @return void
     */
    public function testAssignBundledProductsToProductConcreteShouldAssignBundledProductsAndAvailability(): void
    {
        $bundleAvailability = 5;

        $productBundleRepositoryMock = $this->createProductBundleRepositoryMock();
        $this->setupFindBundledProducts($this->fixtures, $productBundleRepositoryMock);
        $productBundleReaderMock = $this->createProductBundleReader($productBundleRepositoryMock);

        $productConcreteTransfer = new ProductConcreteTransfer();
        $productConcreteTransfer->setIdProductConcrete($this->fixtures['idProductConcrete']);
        $productConcreteTransfer->setSku('sku-2');

        $availabilityTransfer = new ProductConcreteAvailabilityTransfer();
        $availabilityTransfer->setAvailability($bundleAvailability);

        $productBundleReaderMock->method('findProductConcreteAvailabilityBySkuForStore')
            ->willReturn($availabilityTransfer);

        $productConcreteTransfer = $productBundleReaderMock->assignBundledProductsToProductConcrete($productConcreteTransfer);

        $productBundleTransfer = $productConcreteTransfer->getProductBundle();

        $this->assertNotNull($productBundleTransfer);
        $this->assertEquals($bundleAvailability, $productBundleTransfer->getAvailability()->toString());
        $this->assertCount(1, $productBundleTransfer->getBundledProducts());
    }

    /**
     * @param \Spryker\Zed\ProductBundle\Persistence\ProductBundleQueryContainerInterface|null $productBundleQueryContainerMock
     * @param \Spryker\Zed\ProductBundle\Dependency\Facade\ProductBundleToAvailabilityFacadeInterface|null $productBundleToAvailabilityFacadeMock
     * @param \Spryker\Zed\ProductBundle\Dependency\Facade\ProductBundleToStoreFacadeInterface|null $storeFacadeMock
     *
     * @return \Spryker\Zed\ProductBundle\Business\ProductBundle\ProductBundleReader|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createProductBundleReader(
        ?ProductBundleQueryContainerInterface $productBundleQueryContainerMock = null,
        ?ProductBundleToAvailabilityFacadeInterface $productBundleToAvailabilityFacadeMock = null,
        ?ProductBundleToStoreFacadeInterface $storeFacadeMock = null
    ) {
        if ($productBundleQueryContainerMock === null) {
            $productBundleQueryContainerMock = $this->createProductQueryContainerMock();
        }

        if ($productBundleToAvailabilityFacadeMock === null) {
            $productBundleToAvailabilityFacadeMock = $this->createAvailabilityFacadeMock();
        }

        if ($storeFacadeMock === null) {
            $storeFacadeMock = $this->createStoreFacadeMock();
            $storeTransfer = (new StoreBuilder([
                StoreTransfer::ID_STORE => self::ID_STORE,
            ]))->build();
            $storeFacadeMock->method('getCurrentStore')->willReturn($storeTransfer);
            $storeFacadeMock->method('getStoreByName')->willReturn($storeTransfer);
        }

        $productBundleReaderMock = $this->getMockBuilder(ProductBundleReader::class)
            ->setConstructorArgs([$productBundleQueryContainerMock, $productBundleToAvailabilityFacadeMock, $storeFacadeMock ])
            ->setMethods(['findBundledProducts', 'findProductConcreteAvailabilityBySkuForStore'])
            ->getMock();

        return $productBundleReaderMock;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\ProductBundle\Persistence\ProductBundleRepositoryInterface
     */
    protected function createProductBundleRepositoryMock()
    {
        return $this->getMockBuilder(ProductBundleRepositoryInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\ProductBundle\Dependency\Facade\ProductBundleToAvailabilityFacadeInterface
     */
    protected function createAvailabilityFacadeMock()
    {
        return $this->getMockBuilder(ProductBundleToAvailabilityFacadeInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\ProductBundle\Dependency\Facade\ProductBundleToStoreFacadeInterface
     */
    protected function createStoreFacadeMock()
    {
        return $this->getMockBuilder(ProductBundleToStoreFacadeInterface::class)->getMock();
    }

    /**
     * @param array $fixtures
     * @param \PHPUnit\Framework\MockObject\MockObject $productBundleReaderMock
     *
     * @return void
     */
    protected function setupFindBundledProducts(array $fixtures, MockObject $productBundleReaderMock): void
    {
        $productBundleEntity = new SpyProductBundle();
        $productBundleEntity->setIdProductBundle($fixtures['idProductConcrete']);
        $productBundleEntity->setQuantity($fixtures['bundledProductQuantity']);

        $productEntity = new SpyProduct();
        $productEntity->setIdProduct($fixtures['fkBundledProduct']);
        $productEntity->setSku($fixtures['bundledProductSku']);

        $productBundleEntity->setSpyProductRelatedByFkBundledProduct($productEntity);

        $productBundleEntity->setFkBundledProduct($fixtures['fkBundledProduct']);

        $productBundleReaderMock->expects($this->once())
            ->method('findBundledProducts')
            ->with($fixtures['idProductConcrete'])
            ->willReturn([$productBundleEntity]);
    }
}
