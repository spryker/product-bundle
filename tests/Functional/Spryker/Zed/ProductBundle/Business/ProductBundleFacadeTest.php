<?php
/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Functional\Spryker\Zed\ProductBundle\Business;

use Codeception\TestCase\Test;
use Generated\Shared\Transfer\CartChangeTransfer;
use Generated\Shared\Transfer\CheckoutResponseTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\PriceProductTransfer;
use Generated\Shared\Transfer\ProductAbstractTransfer;
use Generated\Shared\Transfer\ProductBundleTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;
use Generated\Shared\Transfer\ProductForBundleTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\StockProductTransfer;
use Spryker\Zed\Availability\Persistence\AvailabilityQueryContainer;
use Spryker\Zed\ProductBundle\Business\ProductBundle\Cart\ProductBundleCartItemGroupKeyExpander;
use Spryker\Zed\ProductBundle\Business\ProductBundleFacade;
use Spryker\Zed\Kernel\Locator;
use Spryker\Zed\ProductBundle\Dependency\Facade\ProductBundleToProductBridge;
use Spryker\Zed\ProductBundle\Dependency\QueryContainer\ProductBundleToAvailabilityQueryContainerBridge;

/**
 * @group Functional
 * @group Spryker
 * @group Zed
 * @group ProductBundle
 * @group Business
 * @group ProductBundleFacadeTest
 */
class ProductBundleFacadeTest extends Test
{
    const SKU_BUNDLED_1 = 'sku-1';
    const SKU_BUNDLED_2 = 'sku-2';
    const BUNDLE_SKU_3 = 'sku-3';

    const BUNDLED_PRODUCT_PRICE_1 = 50;
    const BUNDLED_PRODUCT_PRICE_2 = 100;


    /**
     * @return void
     */
    public function testExpandBundleItemsShouldCreateBundleItemsAndCalculateSplitPrice()
    {
        $bundlePrice = self::BUNDLED_PRODUCT_PRICE_2;

        $productConcreteTransfer = $this->createProductBundle($bundlePrice);

        $cartChangeTransfer = new CartChangeTransfer();
        $cartChangeTransfer->setQuote(new QuoteTransfer());

        $itemTransfer = new ItemTransfer();
        $itemTransfer->setQuantity(1);
        $itemTransfer->setUnitGrossPrice($productConcreteTransfer->getPrice()->getPrice());
        $itemTransfer->setId($productConcreteTransfer->getIdProductConcrete());
        $itemTransfer->setSku($productConcreteTransfer->getSku());

        $cartChangeTransfer->addItem($itemTransfer);

        $productBundleFacade = $this->createProductBundleFacade();
        $cartChangeTransfer = $productBundleFacade->expandBundleItems($cartChangeTransfer);

        $quoteTransfer = $cartChangeTransfer->getQuote();

        $this->assertCount(3, $cartChangeTransfer->getItems());
        $this->assertCount(1, $quoteTransfer->getBundleItems());

        $bundledItemTransfer = $quoteTransfer->getBundleItems()[0];
        $bundleItemIdentifier = $bundledItemTransfer->getBundleItemIdentifier();

        $this->assertSame($bundlePrice, $bundledItemTransfer->getUnitGrossPrice());

        $itemTransfer = $cartChangeTransfer->getItems()[0];
        $this->assertSame(20, $itemTransfer->getUnitGrossPrice());
        $this->assertSame($bundleItemIdentifier, $itemTransfer->getRelatedBundleItemIdentifier());

        $itemTransfer = $cartChangeTransfer->getItems()[1];
        $itemTransfer->getUnitGrossPrice();
        $this->assertSame(40, $itemTransfer->getUnitGrossPrice());
        $this->assertSame($bundleItemIdentifier, $itemTransfer->getRelatedBundleItemIdentifier());

        $itemTransfer = $cartChangeTransfer->getItems()[2];
        $itemTransfer->getUnitGrossPrice();
        $this->assertSame(40, $itemTransfer->getUnitGrossPrice());
        $this->assertSame($bundleItemIdentifier, $itemTransfer->getRelatedBundleItemIdentifier());

    }

    /**
     * @return void
     */
    public function testExpandCartItemGroupKeyShouldAppendBundleToKey()
    {
        $productBundleFacade = $this->createProductBundleFacade();

        $cartChangeTransfer = new CartChangeTransfer();

        $groupKeyBefore = 'test1';
        $itemTransfer = new ItemTransfer();
        $itemTransfer->setGroupKey($groupKeyBefore);
        $itemTransfer->setRelatedBundleItemIdentifier('related1');

        $cartChangeTransfer->addItem($itemTransfer);

        $cartChangeTransfer = $productBundleFacade->expandBundleCartItemGroupKey($cartChangeTransfer);

        $itemTransfer = $cartChangeTransfer->getItems()[0];

        $this->assertEquals(
            $groupKeyBefore . ProductBundleCartItemGroupKeyExpander::GROUP_KEY_DELIMITER . $itemTransfer->getRelatedBundleItemIdentifier() ,
            $itemTransfer->getGroupKey()
        );

    }

    /**
     * @return void
     */
    public function testPostSaveCartUpdateWhenBundleRemoveShouldReturnQuoteWithouBundles()
    {
        $quoteTransfer = new QuoteTransfer();

        $itemTransfer = new ItemTransfer();
        $itemTransfer->setBundleItemIdentifier('bundleid');

        $quoteTransfer->addBundleItem($itemTransfer);

        $productBundleFacade = $this->createProductBundleFacade();
        $quoteTransfer = $productBundleFacade->postSaveCartUpdateBundles($quoteTransfer);

        $this->assertCount(0, $quoteTransfer->getBundleItems());

    }

    /**
     * @return void
     */
    public function testPreCheckCartAvailabilityWhenBundleAvailable()
    {
        $productConcreteTransfer = $this->createProductBundle(self::BUNDLED_PRODUCT_PRICE_2);

        $productBundleFacade = $this->createProductBundleFacade();

        $cartChangeTransfer = new CartChangeTransfer();

        $itemTransfer = new ItemTransfer();
        $itemTransfer->setSku($productConcreteTransfer->getSku());

        $quoteTransfer = new QuoteTransfer();
        $quoteTransfer->addItem($itemTransfer);

        $cartChangeTransfer->setQuote($quoteTransfer);

        $itemTransfer = new ItemTransfer();
        $itemTransfer->setSku($productConcreteTransfer->getSku());
        $itemTransfer->setQuantity(1);

        $cartChangeTransfer->addItem($itemTransfer);

        $cartChangeTransfer = $productBundleFacade->preCheckCartAvailability($cartChangeTransfer);

        $this->assertTrue($cartChangeTransfer->getIsSuccess());
    }

    /**
     * @return void
     */
    public function testPreCheckCartAvailabilityWhenBundleUnavailable()
    {
        $productConcreteTransfer = $this->createProductBundle(self::BUNDLED_PRODUCT_PRICE_2);

        $productBundleFacade = $this->createProductBundleFacade();

        $cartChangeTransfer = new CartChangeTransfer();

        $itemTransfer = new ItemTransfer();
        $itemTransfer->setQuantity(5);
        $itemTransfer->setSku($productConcreteTransfer->getSku());

        $quoteTransfer = new QuoteTransfer();
        $quoteTransfer->addItem($itemTransfer);

        $cartChangeTransfer->setQuote($quoteTransfer);

        $itemTransfer = new ItemTransfer();
        $itemTransfer->setSku(self::SKU_BUNDLED_1);
        $itemTransfer->setQuantity(25);

        $cartChangeTransfer->addItem($itemTransfer);

        $cartChangeTransfer = $productBundleFacade->preCheckCartAvailability($cartChangeTransfer);

        $this->assertFalse($cartChangeTransfer->getIsSuccess());
    }

    /**
     * @return void
     */
    public function testPreCheckCheckoutAvailabilityWhenBundleUnavailable()
    {
        $productConcreteTransfer = $this->createProductBundle(self::BUNDLED_PRODUCT_PRICE_2);

        $productBundleFacade = $this->createProductBundleFacade();

        $quoteTransfer = new QuoteTransfer();

        $itemTransfer = new ItemTransfer();
        $itemTransfer->setQuantity(50);
        $itemTransfer->setSku(self::SKU_BUNDLED_1);

        $quoteTransfer->addItem($itemTransfer);

        $itemTransfer = new ItemTransfer();
        $itemTransfer->setQuantity(1);
        $itemTransfer->setSku($productConcreteTransfer->getSku());
        $quoteTransfer->addBundleItem($itemTransfer);

        $checkoutResponseTransfer = new CheckoutResponseTransfer();

        $productBundleFacade->preCheckCheckoutAvailability($quoteTransfer, $checkoutResponseTransfer);

        $this->assertFalse($checkoutResponseTransfer->getIsSuccess());
    }

    /**
     * @return void
     */
    public function testPreCheckCheckoutAvailabilityWhenBundleAvailable()
    {
        $productConcreteTransfer = $this->createProductBundle(self::BUNDLED_PRODUCT_PRICE_2);

        $productBundleFacade = $this->createProductBundleFacade();

        $quoteTransfer = new QuoteTransfer();

        $itemTransfer = new ItemTransfer();
        $itemTransfer->setQuantity(1);
        $itemTransfer->setSku(self::SKU_BUNDLED_1);

        $quoteTransfer->addItem($itemTransfer);

        $itemTransfer = new ItemTransfer();
        $itemTransfer->setQuantity(1);
        $itemTransfer->setSku($productConcreteTransfer->getSku());
        $quoteTransfer->addBundleItem($itemTransfer);

        $checkoutResponseTransfer = new CheckoutResponseTransfer();

        $productBundleFacade->preCheckCheckoutAvailability($quoteTransfer, $checkoutResponseTransfer);

        $this->assertNull($checkoutResponseTransfer->getIsSuccess());
    }

    /**
     * @return void
     */
    public function testUpdateAffectedBundleAvailabilityWhenOneOfBundledItemsUnavailable()
    {
        $this->createProductBundle(self::BUNDLED_PRODUCT_PRICE_2);

        $productBundleFacade = $this->createProductBundleFacade();

        $availabilityQueryContainer = $this->createAvailabilityQueryContainer();
        $bundledProductAvailability = $availabilityQueryContainer->querySpyAvailabilityBySku(self::SKU_BUNDLED_2)->findOne();

        $bundledProductAvailability->setQuantity(0)
            ->save();

        $productBundleFacade->updateAffectedBundlesAvailability(self::SKU_BUNDLED_2);

        $bundledProductAvailability = $availabilityQueryContainer->querySpyAvailabilityBySku(self::BUNDLE_SKU_3)->findOne();

        $this->assertSame(0, $bundledProductAvailability->getQuantity());

    }

    /**
     * @return void
     */
    public function testSaveSalesOrderBundleItems()
    {


    }

    public function testSaveBundledProducts()
    {

    }

    /**
     * @return \Spryker\Zed\ProductBundle\Business\ProductBundleFacade
     */
    protected function createProductBundleFacade()
    {
        return new ProductBundleFacade();
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Dependency\Facade\ProductBundleToProductBridge
     */
    protected function createProductFacade()
    {
        return new ProductBundleToProductBridge($this->getLocator()->product()->facade());
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Dependency\QueryContainer\ProductBundleToAvailabilityQueryContainerBridge
     */
    protected function createAvailabilityQueryContainer()
    {
        return new ProductBundleToAvailabilityQueryContainerBridge($this->getLocator()->availability()->queryContainer());
    }

    /**
     * @return \Generated\Zed\Ide\AutoCompletion|\Spryker\Shared\Kernel\LocatorLocatorInterface
     */
    protected function getLocator()
    {
        return Locator::getInstance();
    }

    /**
     * @param int $price
     * @param string $sku
     *
     * @return \Generated\Shared\Transfer\ProductConcreteTransfer
     */
    protected function createProduct($price, $sku)
    {
        $productFacade = $this->createProductFacade();
        $productAbstractTransfer = new ProductAbstractTransfer();
        $productAbstractTransfer->setSku(123 . rand(1, 9999));

        $priceProductTransfer = new PriceProductTransfer();
        $priceProductTransfer->setPrice($price);

        $productAbstractTransfer->setPrice($priceProductTransfer);
        $productAbstractTransfer->setAttributes([]);

        $concreteProductCollection = [];

        $stockProductTransfer = new StockProductTransfer();
        $stockProductTransfer->setSku($sku);
        $stockProductTransfer->setQuantity(10);
        $stockProductTransfer->setStockType('Warehouse1');

        $productConcreteTransfer = new ProductConcreteTransfer();
        $productConcreteTransfer->addStock($stockProductTransfer);
        $productConcreteTransfer->setSku($sku);
        $productConcreteTransfer->setIsActive(false);
        $productConcreteTransfer->setPrice($productAbstractTransfer->getPrice());
        $productConcreteTransfer->setLocalizedAttributes($productAbstractTransfer->getLocalizedAttributes());

        $concreteProductCollection[] = $productConcreteTransfer;

        $productFacade->addProduct($productAbstractTransfer, $concreteProductCollection);

        return $productConcreteTransfer;
    }

    /**
     * @param int $bundlePrice
     *
     * @return ProductConcreteTransfer
     */
    protected function createProductBundle($bundlePrice)
    {
        $productConcreteTransferToAssign1 = $this->createProduct(self::BUNDLED_PRODUCT_PRICE_1, self::SKU_BUNDLED_1);
        $productConcreteTransferToAssign2 = $this->createProduct(self::BUNDLED_PRODUCT_PRICE_2, self::SKU_BUNDLED_2);

        $productBundleTransfer = new ProductBundleTransfer();

        $bundledProductTransfer = new ProductForBundleTransfer();
        $bundledProductTransfer->setQuantity(1);
        $bundledProductTransfer->setIdProductConcrete($productConcreteTransferToAssign1->getIdProductConcrete());
        $productBundleTransfer->addBundledProduct($bundledProductTransfer);

        $bundledProductTransfer = new ProductForBundleTransfer();
        $bundledProductTransfer->setQuantity(2);
        $bundledProductTransfer->setIdProductConcrete($productConcreteTransferToAssign2->getIdProductConcrete());
        $productBundleTransfer->addBundledProduct($bundledProductTransfer);

        $productConcreteTransfer = $this->createProduct($bundlePrice, self::BUNDLE_SKU_3);
        $productConcreteTransfer->setProductBundle($productBundleTransfer);

        $productBundleFacade = $this->createProductBundleFacade();
        $productBundleFacade->saveBundledProducts($productConcreteTransfer);

        return $productConcreteTransfer;
    }

}

