<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\ProductBundle\Business;

use ArrayObject;
use Codeception\Test\Unit;
use Generated\Shared\DataBuilder\StoreBuilder;
use Generated\Shared\Transfer\ProductBundleTransfer;
use Generated\Shared\Transfer\ProductConcreteAvailabilityTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Orm\Zed\Product\Persistence\SpyProduct;
use Orm\Zed\ProductBundle\Persistence\SpyProductBundle;
use PHPUnit\Framework\MockObject\MockObject;
use Spryker\DecimalObject\Decimal;
use Spryker\Zed\ProductBundle\Business\ProductBundle\Cache\ProductBundleCache;
use Spryker\Zed\ProductBundle\Business\ProductBundle\Cache\ProductBundleCacheInterface;
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
    /**
     * @var int
     */
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
        $productBundleReaderMock = $this->createProductBundleReader();

        $this->setupFindBundledProducts($this->fixtures, $productBundleReaderMock);

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
        // Arrange
        $bundleAvailability = 5;

        $productBundleReaderMock = $this->createProductBundleReader(
            null,
            null,
            null,
            $this->setupGetProductBundleTransfersIndexedByIdProductConcrete($this->fixtures),
        );

        $productConcreteTransfer = new ProductConcreteTransfer();
        $productConcreteTransfer->setIdProductConcrete($this->fixtures['idProductConcrete']);
        $sku = 'sku-2';
        $productConcreteTransfer->setSku($sku);

        $availabilityTransfer = new ProductConcreteAvailabilityTransfer();
        $availabilityTransfer->setAvailability($bundleAvailability);

        $productBundleReaderMock->method('getProductConcreteAvailabilityIndexedBySkuForStore')
            ->willReturn([$sku => $availabilityTransfer]);

        // Action
        $productConcreteTransfer = $productBundleReaderMock->assignBundledProductsToProductConcrete($productConcreteTransfer);

        // Asssertion
        $productBundleTransfer = $productConcreteTransfer->getProductBundle();

        $this->assertNotNull($productBundleTransfer);
        $this->assertSame((string)$bundleAvailability, $productBundleTransfer->getAvailability()->toString());
        $this->assertCount(1, $productBundleTransfer->getBundledProducts());
    }

    /**
     * @param array $fixtures
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\ProductBundle\Persistence\ProductBundleRepositoryInterface
     */
    protected function setupGetProductBundleTransfersIndexedByIdProductConcrete(array $fixtures): ProductBundleRepositoryInterface
    {
        $productBundleRepositoryMock = $this->createProductBundleRepositoryMock();

        $productBundleTransfer = new ProductBundleTransfer();
        $productBundleTransfer->setIdProductConcreteBundle($fixtures['idProductConcrete']);

        $productConcreteTransfer = new ProductConcreteTransfer();
        $productConcreteTransfer->setIdProductConcrete($fixtures['fkBundledProduct']);
        $productConcreteTransfer->setSku($fixtures['bundledProductSku']);

        $productBundleTransfer->setBundledProducts(new ArrayObject([$productConcreteTransfer]));

        $productBundleRepositoryMock->expects($this->once())
            ->method('getProductBundleTransfersIndexedByIdProductConcrete')
            ->with([$fixtures['idProductConcrete']])
            ->willReturn([$fixtures['idProductConcrete'] => $productBundleTransfer]);

        return $productBundleRepositoryMock;
    }

    /**
     * @param \Spryker\Zed\ProductBundle\Persistence\ProductBundleQueryContainerInterface|null $productBundleQueryContainerMock
     * @param \Spryker\Zed\ProductBundle\Dependency\Facade\ProductBundleToAvailabilityFacadeInterface|null $productBundleToAvailabilityFacadeMock
     * @param \Spryker\Zed\ProductBundle\Dependency\Facade\ProductBundleToStoreFacadeInterface|null $storeFacadeMock
     * @param \Spryker\Zed\ProductBundle\Persistence\ProductBundleRepositoryInterface|null $productBundleRepository
     *
     * @return \Spryker\Zed\ProductBundle\Business\ProductBundle\ProductBundleReader|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createProductBundleReader(
        ?ProductBundleQueryContainerInterface $productBundleQueryContainerMock = null,
        ?ProductBundleToAvailabilityFacadeInterface $productBundleToAvailabilityFacadeMock = null,
        ?ProductBundleToStoreFacadeInterface $storeFacadeMock = null,
        ?ProductBundleRepositoryInterface $productBundleRepository = null
    ): ProductBundleReader {
        if ($productBundleQueryContainerMock === null) {
            $productBundleQueryContainerMock = $this->createProductQueryContainerMock();
        }

        if ($productBundleToAvailabilityFacadeMock === null) {
            $productBundleToAvailabilityFacadeMock = $this->createAvailabilityFacadeMock();
        }

        if ($storeFacadeMock === null) {
            $storeFacadeMock = $this->createStoreFacadeMock();
            $storeTransfer = (new StoreBuilder([
                StoreTransfer::ID_STORE => static::ID_STORE,
            ]))
                ->build();
            $storeFacadeMock->method('getCurrentStore')->willReturn($storeTransfer);
            $storeFacadeMock->method('getStoreByName')->willReturn($storeTransfer);
        }

        if ($productBundleRepository === null) {
            $productBundleRepository = $this->createProductBundleRepositoryMock();
        }

        $productBundleCache = $this->createProductBundleCache();

        $productBundleReaderMock = $this->getMockBuilder(ProductBundleReader::class)
            ->setConstructorArgs([
                $productBundleQueryContainerMock,
                $productBundleToAvailabilityFacadeMock,
                $storeFacadeMock,
                $productBundleRepository,
                $productBundleCache,
            ])
            ->setMethods(['findBundledProducts', 'getProductConcreteAvailabilityIndexedBySkuForStore'])
            ->getMock();

        return $productBundleReaderMock;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\ProductBundle\Persistence\ProductBundleQueryContainerInterface
     */
    protected function createProductQueryContainerMock(): ProductBundleQueryContainerInterface
    {
        return $this->getMockBuilder(ProductBundleQueryContainerInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\ProductBundle\Dependency\Facade\ProductBundleToAvailabilityFacadeInterface
     */
    protected function createAvailabilityFacadeMock(): ProductBundleToAvailabilityFacadeInterface
    {
        return $this->getMockBuilder(ProductBundleToAvailabilityFacadeInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\ProductBundle\Persistence\ProductBundleRepositoryInterface
     */
    protected function createProductBundleRepositoryMock(): ProductBundleRepositoryInterface
    {
        return $this->getMockBuilder(ProductBundleRepositoryInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\ProductBundle\Dependency\Facade\ProductBundleToStoreFacadeInterface
     */
    protected function createStoreFacadeMock(): ProductBundleToStoreFacadeInterface
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

    /**
     * @return \Spryker\Zed\ProductBundle\Business\ProductBundle\Cache\ProductBundleCacheInterface
     */
    protected function createProductBundleCache(): ProductBundleCacheInterface
    {
        return new ProductBundleCache();
    }
}
