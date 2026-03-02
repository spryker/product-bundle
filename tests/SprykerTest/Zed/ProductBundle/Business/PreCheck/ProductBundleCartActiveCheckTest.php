<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\ProductBundle\Business\PreCheck;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CartChangeTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\ProductForBundleTransfer;
use Spryker\Zed\ProductBundle\Business\ProductBundle\PreCheck\ProductBundleCartActiveCheck;
use Spryker\Zed\ProductBundle\Business\ProductBundle\PreCheck\ProductBundleCartActiveCheckInterface;
use Spryker\Zed\ProductBundle\Business\ProductBundle\ProductBundleReaderInterface;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group ProductBundle
 * @group Business
 * @group PreCheck
 * @group ProductBundleCartActiveCheckTest
 * Add your own group annotations below this line
 */
class ProductBundleCartActiveCheckTest extends Unit
{
    /**
     * @var string
     */
    protected const PRODUCT_SKU_INACTIVE = 'inactive';

    /**
     * @var string
     */
    protected const PRODUCT_SKU_ACTIVE = 'active';

    public function testCheckActiveItemsShouldReturnErrorMessageIfBundleProductIsNotActive(): void
    {
        $productBundleCartActiveCheck = $this->createProductBundleCartActiveCheck(static::PRODUCT_SKU_INACTIVE);
        $cartPreCheckResponseTransfer = $productBundleCartActiveCheck->checkActiveItems(
            $this->createCartChangeTransferWithProduct(static::PRODUCT_SKU_INACTIVE),
        );

        $this->assertFalse($cartPreCheckResponseTransfer->getIsSuccess());
        $this->assertNotEmpty($cartPreCheckResponseTransfer->getMessages());
    }

    public function testCheckActiveItemsShouldReturnNoMessagesIfBundleProductIsActive(): void
    {
        $productBundleCartActiveCheck = $this->createProductBundleCartActiveCheck(static::PRODUCT_SKU_ACTIVE);
        $cartPreCheckResponseTransfer = $productBundleCartActiveCheck->checkActiveItems(
            $this->createCartChangeTransferWithProduct(static::PRODUCT_SKU_ACTIVE),
        );

        $this->assertTrue($cartPreCheckResponseTransfer->getIsSuccess());
        $this->assertEmpty($cartPreCheckResponseTransfer->getMessages());
    }

    protected function createCartChangeTransferWithProduct(string $sku): CartChangeTransfer
    {
        $itemTransfer = (new ItemTransfer())
            ->setSku($sku);

        return (new CartChangeTransfer())
            ->addItem($itemTransfer);
    }

    protected function createProductBundleCartActiveCheck(string $sku): ProductBundleCartActiveCheckInterface
    {
        return new ProductBundleCartActiveCheck(
            $this->createProductBundleReaderMock($sku),
        );
    }

    protected function createProductForBundleTransfer(string $sku): ProductForBundleTransfer
    {
        return (new ProductForBundleTransfer())
            ->setSku($sku)
            ->setIsActive($sku === static::PRODUCT_SKU_ACTIVE);
    }

    /**
     * @param string $sku
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\ProductBundle\Business\ProductBundle\ProductBundleReaderInterface
     */
    protected function createProductBundleReaderMock(string $sku): ProductBundleReaderInterface
    {
        $productBundleReaderMock = $this->getMockBuilder(ProductBundleReaderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productBundleReaderMock->method('getProductForBundleTransfersByProductConcreteSkus')
            ->willReturn([$sku => [$this->createProductForBundleTransfer($sku)]]);

        return $productBundleReaderMock;
    }
}
