<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\ProductBundle\Business\ProductBundle\Store\PreCheck;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CheckoutResponseTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;
use Generated\Shared\Transfer\ProductCriteriaTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Spryker\Zed\ProductBundle\Business\ProductBundle\Store\PreCheck\ProductBundleStoreCheckoutCheck;
use Spryker\Zed\ProductBundle\Dependency\Facade\ProductBundleToProductFacadeInterface;
use Spryker\Zed\ProductBundle\Dependency\Facade\ProductBundleToStoreFacadeInterface;
use Spryker\Zed\ProductBundle\Exception\MissingFacadeMethodException;
use Spryker\Zed\ProductBundle\Persistence\ProductBundleRepositoryInterface;
use Spryker\Zed\ProductBundle\ProductBundleConfig;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group ProductBundle
 * @group Business
 * @group ProductBundle
 * @group Store
 * @group PreCheck
 * @group ProductBundleStoreCheckoutCheckTest
 * Add your own group annotations below this line
 */
class ProductBundleStoreCheckoutCheckTest extends Unit
{
    protected const string STORE_NAME_DE = 'DE';

    protected const int ID_STORE_DE = 1;

    protected const string BUNDLE_SKU = 'bundle-sku';

    protected const string BUNDLED_PRODUCT_SKU_1 = 'bundled-sku-1';

    protected const string BUNDLED_PRODUCT_SKU_2 = 'bundled-sku-2';

    protected const string GROUP_KEY = 'bundle-sku-group-key';

    protected const string GLOSSARY_KEY_PRODUCT_UNAVAILABLE = 'product.unavailable';

    protected const string GLOSSARY_KEY_BUNDLE_ITEM_UNAVAILABLE = 'product_bundle.unavailable';

    public function testCheckCheckoutStoreAssignmentReturnsTrueWhenBundleAndBundledProductsAreAssignedToStore(): void
    {
        // Arrange
        $productBundleStoreCheckoutCheck = $this->createProductBundleStoreCheckoutCheck(
            [static::BUNDLE_SKU, static::BUNDLED_PRODUCT_SKU_1, static::BUNDLED_PRODUCT_SKU_2],
        );
        $checkoutResponseTransfer = (new CheckoutResponseTransfer())->setIsSuccess(true);

        // Act
        $isSuccessful = $productBundleStoreCheckoutCheck->checkCheckoutStoreAssignment(
            $this->createQuoteTransfer(),
            $checkoutResponseTransfer,
        );

        // Assert
        $this->assertTrue($isSuccessful);
        $this->assertTrue($checkoutResponseTransfer->getIsSuccess());
        $this->assertCount(0, $checkoutResponseTransfer->getErrors());
    }

    public function testCheckCheckoutStoreAssignmentReturnsTrueWhenQuoteHasNoBundleItems(): void
    {
        // Arrange
        $productBundleStoreCheckoutCheck = $this->createProductBundleStoreCheckoutCheck([]);
        $checkoutResponseTransfer = (new CheckoutResponseTransfer())->setIsSuccess(true);

        $quoteTransfer = (new QuoteTransfer())
            ->setStore((new StoreTransfer())->setName(static::STORE_NAME_DE));

        // Act
        $isSuccessful = $productBundleStoreCheckoutCheck->checkCheckoutStoreAssignment(
            $quoteTransfer,
            $checkoutResponseTransfer,
        );

        // Assert
        $this->assertTrue($isSuccessful);
        $this->assertCount(0, $checkoutResponseTransfer->getErrors());
    }

    public function testCheckCheckoutStoreAssignmentAddsErrorWhenBundleIsNotAssignedToStore(): void
    {
        // Arrange
        $productBundleStoreCheckoutCheck = $this->createProductBundleStoreCheckoutCheck(
            [static::BUNDLED_PRODUCT_SKU_1, static::BUNDLED_PRODUCT_SKU_2],
        );
        $checkoutResponseTransfer = (new CheckoutResponseTransfer())->setIsSuccess(true);

        // Act
        $isSuccessful = $productBundleStoreCheckoutCheck->checkCheckoutStoreAssignment(
            $this->createQuoteTransfer(),
            $checkoutResponseTransfer,
        );

        // Assert
        $this->assertFalse($isSuccessful);
        $this->assertFalse($checkoutResponseTransfer->getIsSuccess());
        $this->assertCount(1, $checkoutResponseTransfer->getErrors());

        $checkoutErrorTransfer = $checkoutResponseTransfer->getErrors()->offsetGet(0);
        $this->assertSame(static::GLOSSARY_KEY_PRODUCT_UNAVAILABLE, $checkoutErrorTransfer->getMessage());
        $this->assertSame(['%sku%' => static::BUNDLE_SKU], $checkoutErrorTransfer->getParameters());
        $this->assertSame(static::GROUP_KEY, $checkoutErrorTransfer->getGroupKey());
    }

    public function testCheckCheckoutStoreAssignmentAddsErrorPerBundledProductNotAssignedToStore(): void
    {
        // Arrange
        $productBundleStoreCheckoutCheck = $this->createProductBundleStoreCheckoutCheck(
            [static::BUNDLE_SKU, static::BUNDLED_PRODUCT_SKU_1],
        );
        $checkoutResponseTransfer = (new CheckoutResponseTransfer())->setIsSuccess(true);

        // Act
        $isSuccessful = $productBundleStoreCheckoutCheck->checkCheckoutStoreAssignment(
            $this->createQuoteTransfer(),
            $checkoutResponseTransfer,
        );

        // Assert
        $this->assertFalse($isSuccessful);
        $this->assertCount(1, $checkoutResponseTransfer->getErrors());

        $checkoutErrorTransfer = $checkoutResponseTransfer->getErrors()->offsetGet(0);
        $this->assertSame(static::GLOSSARY_KEY_BUNDLE_ITEM_UNAVAILABLE, $checkoutErrorTransfer->getMessage());
        $this->assertSame(
            [
                '%bundleSku%' => static::BUNDLE_SKU,
                '%productSku%' => static::BUNDLED_PRODUCT_SKU_2,
            ],
            $checkoutErrorTransfer->getParameters(),
        );
    }

    public function testCheckCheckoutStoreAssignmentFiltersByStoreResolvedByNameWhenQuoteStoreHasNoIdStore(): void
    {
        // Arrange
        $productFacadeMock = $this->getMockBuilder(ProductBundleToProductFacadeInterface::class)->getMock();
        $productFacadeMock->expects($this->once())
            ->method('getProductConcretesByCriteria')
            ->with($this->callback(function (ProductCriteriaTransfer $productCriteriaTransfer): bool {
                return $productCriteriaTransfer->getIdStore() === static::ID_STORE_DE;
            }))
            ->willReturn([]);

        $productBundleStoreCheckoutCheck = $this->createProductBundleStoreCheckoutCheck([], $productFacadeMock);

        // Act
        $isSuccessful = $productBundleStoreCheckoutCheck->checkCheckoutStoreAssignment(
            $this->createQuoteTransfer(),
            (new CheckoutResponseTransfer())->setIsSuccess(true),
        );

        // Assert
        $this->assertFalse($isSuccessful);
    }

    public function testCheckCheckoutStoreAssignmentResolvesBundledProductsOfAllBundlesInOneRepositoryCall(): void
    {
        // Arrange
        $productBundleRepositoryMock = $this->getMockBuilder(ProductBundleRepositoryInterface::class)->getMock();
        $productBundleRepositoryMock->expects($this->once())
            ->method('getBundledProductSkusGroupedByBundleProductSku')
            ->with([static::BUNDLE_SKU])
            ->willReturn([static::BUNDLE_SKU => [static::BUNDLED_PRODUCT_SKU_1, static::BUNDLED_PRODUCT_SKU_2]]);

        $productFacadeMock = $this->getMockBuilder(ProductBundleToProductFacadeInterface::class)->getMock();
        $productFacadeMock->expects($this->once())
            ->method('getProductConcretesByCriteria')
            ->with($this->callback(function (ProductCriteriaTransfer $productCriteriaTransfer): bool {
                return $productCriteriaTransfer->getSkus() === [
                    static::BUNDLE_SKU,
                    static::BUNDLED_PRODUCT_SKU_1,
                    static::BUNDLED_PRODUCT_SKU_2,
                ];
            }))
            ->willReturn([]);

        $productBundleStoreCheckoutCheck = new ProductBundleStoreCheckoutCheck(
            $productFacadeMock,
            $this->createStoreFacadeMock(),
            $productBundleRepositoryMock,
            $this->createProductBundleConfigMock(),
        );

        $quoteTransfer = $this->createQuoteTransfer();
        $quoteTransfer->addBundleItem($this->createBundleItemTransfer());

        // Act
        $isSuccessful = $productBundleStoreCheckoutCheck->checkCheckoutStoreAssignment(
            $quoteTransfer,
            (new CheckoutResponseTransfer())->setIsSuccess(true),
        );

        // Assert
        $this->assertFalse($isSuccessful);
    }

    public function testCheckCheckoutStoreAssignmentChecksBundleOnceWhenSeveralUnitsOfTheSameBundleAreInCart(): void
    {
        // Arrange
        $productBundleStoreCheckoutCheck = $this->createProductBundleStoreCheckoutCheck(
            [static::BUNDLED_PRODUCT_SKU_1, static::BUNDLED_PRODUCT_SKU_2],
        );
        $checkoutResponseTransfer = (new CheckoutResponseTransfer())->setIsSuccess(true);

        $quoteTransfer = $this->createQuoteTransfer();
        $quoteTransfer->addBundleItem($this->createBundleItemTransfer());

        // Act
        $isSuccessful = $productBundleStoreCheckoutCheck->checkCheckoutStoreAssignment(
            $quoteTransfer,
            $checkoutResponseTransfer,
        );

        // Assert
        $this->assertFalse($isSuccessful);
        $this->assertCount(1, $checkoutResponseTransfer->getErrors());
    }

    public function testCheckCheckoutStoreAssignmentSkipsBundlesFromTheOriginalOrder(): void
    {
        // Arrange
        $productBundleStoreCheckoutCheck = $this->createProductBundleStoreCheckoutCheck([]);
        $checkoutResponseTransfer = (new CheckoutResponseTransfer())->setIsSuccess(true);

        // Act
        $isSuccessful = $productBundleStoreCheckoutCheck->checkCheckoutStoreAssignment(
            $this->createQuoteTransfer(),
            $checkoutResponseTransfer,
            [static::BUNDLE_SKU => static::BUNDLE_SKU],
        );

        // Assert
        $this->assertTrue($isSuccessful);
        $this->assertCount(0, $checkoutResponseTransfer->getErrors());
    }

    public function testCheckCheckoutStoreAssignmentReturnsTrueWhenProductFacadeDoesNotSupportGetProductConcretesByCriteria(): void
    {
        // Arrange
        $productFacadeMock = $this->getMockBuilder(ProductBundleToProductFacadeInterface::class)->getMock();
        $productFacadeMock->method('getProductConcretesByCriteria')
            ->willThrowException(new MissingFacadeMethodException());

        $productBundleStoreCheckoutCheck = $this->createProductBundleStoreCheckoutCheck([], $productFacadeMock);
        $checkoutResponseTransfer = (new CheckoutResponseTransfer())->setIsSuccess(true);

        // Act
        $isSuccessful = $productBundleStoreCheckoutCheck->checkCheckoutStoreAssignment(
            $this->createQuoteTransfer(),
            $checkoutResponseTransfer,
        );

        // Assert
        $this->assertTrue($isSuccessful);
        $this->assertTrue($checkoutResponseTransfer->getIsSuccess());
        $this->assertCount(0, $checkoutResponseTransfer->getErrors());
    }

    protected function createQuoteTransfer(): QuoteTransfer
    {
        return (new QuoteTransfer())
            ->setStore((new StoreTransfer())->setName(static::STORE_NAME_DE))
            ->addBundleItem($this->createBundleItemTransfer());
    }

    protected function createBundleItemTransfer(): ItemTransfer
    {
        return (new ItemTransfer())
            ->setSku(static::BUNDLE_SKU)
            ->setGroupKey(static::GROUP_KEY)
            ->setQuantity(1);
    }

    /**
     * @param list<string> $skusAssignedToStore
     */
    protected function createProductBundleStoreCheckoutCheck(
        array $skusAssignedToStore,
        ?ProductBundleToProductFacadeInterface $productFacadeMock = null
    ): ProductBundleStoreCheckoutCheck {
        return new ProductBundleStoreCheckoutCheck(
            $productFacadeMock ?? $this->createProductFacadeMock($skusAssignedToStore),
            $this->createStoreFacadeMock(),
            $this->createProductBundleRepositoryMock(),
            $this->createProductBundleConfigMock(),
        );
    }

    protected function createProductBundleRepositoryMock(): ProductBundleRepositoryInterface
    {
        $productBundleRepositoryMock = $this->getMockBuilder(ProductBundleRepositoryInterface::class)->getMock();
        $productBundleRepositoryMock->method('getBundledProductSkusGroupedByBundleProductSku')
            ->willReturn([
                static::BUNDLE_SKU => [static::BUNDLED_PRODUCT_SKU_1, static::BUNDLED_PRODUCT_SKU_2],
            ]);

        return $productBundleRepositoryMock;
    }

    /**
     * @param list<string> $skusAssignedToStore
     */
    protected function createProductFacadeMock(array $skusAssignedToStore): ProductBundleToProductFacadeInterface
    {
        $productConcreteTransfers = [];

        foreach ($skusAssignedToStore as $sku) {
            $productConcreteTransfers[] = (new ProductConcreteTransfer())->setSku($sku);
        }

        $productFacadeMock = $this->getMockBuilder(ProductBundleToProductFacadeInterface::class)->getMock();
        $productFacadeMock->method('getProductConcretesByCriteria')->willReturn($productConcreteTransfers);

        return $productFacadeMock;
    }

    protected function createStoreFacadeMock(): ProductBundleToStoreFacadeInterface
    {
        $storeFacadeMock = $this->getMockBuilder(ProductBundleToStoreFacadeInterface::class)->getMock();
        $storeFacadeMock->method('getStoreByName')
            ->with(static::STORE_NAME_DE)
            ->willReturn(
                (new StoreTransfer())
                    ->setIdStore(static::ID_STORE_DE)
                    ->setName(static::STORE_NAME_DE),
            );

        return $storeFacadeMock;
    }

    protected function createProductBundleConfigMock(): ProductBundleConfig
    {
        return $this->getMockBuilder(ProductBundleConfig::class)->getMock();
    }
}
