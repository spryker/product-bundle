<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\ProductBundle\Business;

use Codeception\Test\Unit;
use Generated\Shared\DataBuilder\QuoteBuilder;
use Generated\Shared\Transfer\CartChangeTransfer;
use Generated\Shared\Transfer\CheckoutResponseTransfer;
use Generated\Shared\Transfer\CurrencyTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\MoneyValueTransfer;
use Generated\Shared\Transfer\PriceProductTransfer;
use Generated\Shared\Transfer\ProductBundleCriteriaFilterTransfer;
use Generated\Shared\Transfer\ProductBundleTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;
use Generated\Shared\Transfer\ProductForBundleTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\StockProductTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Orm\Zed\ProductBundle\Persistence\SpyProductBundleQuery;
use Spryker\Zed\ProductBundle\Business\ProductBundle\Cart\ProductBundleCartItemGroupKeyExpander;
use Spryker\Zed\ProductBundle\Business\ProductBundleFacade;
use Spryker\Zed\ProductBundle\Business\ProductBundleFacadeInterface;
use Spryker\Zed\ProductBundle\Dependency\Facade\ProductBundleToAvailabilityFacadeBridge;
use Spryker\Zed\ProductBundle\Dependency\Facade\ProductBundleToAvailabilityFacadeInterface;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group ProductBundle
 * @group Business
 * @group Facade
 * @group ProductBundleFacadeTest
 * Add your own group annotations below this line
 */
class ProductBundleFacadeTest extends Unit
{
    public const SKU_BUNDLED_1 = 'sku-1-test-tester';
    public const SKU_BUNDLED_2 = 'sku-2-test-tester';
    public const BUNDLE_SKU_3 = 'sku-3-test-tester';

    public const BUNDLED_PRODUCT_PRICE_1 = 50;
    public const BUNDLED_PRODUCT_PRICE_2 = 100;
    public const ID_STORE = 1;

    /**
     * @var \SprykerTest\Zed\ProductBundle\ProductBundleBusinessTester
     */
    protected $tester;

    /**
     * @return void
     */
    public function testExpandBundleItemsShouldCreateBundleItemsAndCalculateSplitPrice(): void
    {
        $this->markTestSkipped();
        $bundlePrice = static::BUNDLED_PRODUCT_PRICE_2;

        $productConcreteTransfer = $this->createProductBundle($bundlePrice);

        $cartChangeTransfer = new CartChangeTransfer();
        $currencyTransfer = new CurrencyTransfer();
        $currencyTransfer->setCode('EUR');
        $quoteTransfer = $this->createBaseQuoteTransfer();
        $quoteTransfer->setCurrency($currencyTransfer);
        $cartChangeTransfer->setQuote($quoteTransfer);

        $itemTransfer = new ItemTransfer();
        $itemTransfer->setQuantity(static::ID_STORE);
        $itemTransfer->setUnitGrossPrice($productConcreteTransfer->getPrices()[0]->getPrice());
        $itemTransfer->setId($productConcreteTransfer->getIdProductConcrete());
        $itemTransfer->setSku($productConcreteTransfer->getSku());

        $cartChangeTransfer->addItem($itemTransfer);

        $productBundleFacade = $this->createProductBundleFacade();
        $cartChangeTransfer = $productBundleFacade->expandBundleItems($cartChangeTransfer);

        $quoteTransfer = $cartChangeTransfer->getQuote();

        $this->assertCount(3, $cartChangeTransfer->getItems());
        $this->assertCount(static::ID_STORE, $quoteTransfer->getBundleItems());

        $bundledItemTransfer = $quoteTransfer->getBundleItems()[0];
        $bundleItemIdentifier = $bundledItemTransfer->getBundleItemIdentifier();

        $this->assertSame($bundlePrice, $bundledItemTransfer->getUnitGrossPrice());

        $itemTransfer = $cartChangeTransfer->getItems()[0];
        $this->assertSame(20, $itemTransfer->getUnitGrossPrice());
        $this->assertSame($bundleItemIdentifier, $itemTransfer->getRelatedBundleItemIdentifier());

        $itemTransfer = $cartChangeTransfer->getItems()[static::ID_STORE];
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
    public function testGetProductBundleCollectionByCriteriaFilterShouldReturnPersistedBundledProducts(): void
    {
        //Assign
        $productConcreteBundleTransfer = $this->createProductBundle(static::BUNDLED_PRODUCT_PRICE_2);
        $productBundleFacade = $this->createProductBundleFacade();

        foreach ($productConcreteBundleTransfer->getProductBundle()->getBundledProducts() as $bundledProduct) {
            //Act
            $productBundleCollection = $productBundleFacade->getProductBundleCollectionByCriteriaFilter(
                (new ProductBundleCriteriaFilterTransfer())->setIdBundledProduct($bundledProduct->getIdProductConcrete())
            );

            /** @var \Generated\Shared\Transfer\ProductBundleTransfer $productBundleTransfer */
            $productBundleTransfer = $productBundleCollection->getProductBundles()->offsetGet(0);
            $bundleProduct = SpyProductBundleQuery::create()->findOneByFkBundledProduct($bundledProduct->getIdProductConcrete());

            //Assert
            $this->assertSame(1, $productBundleCollection->getProductBundles()->count());
            $this->assertSame($bundleProduct->getFkProduct(), $productBundleTransfer->getIdProductConcreteBundle());
        }
    }

    /**
     * @return void
     */
    public function testExpandCartItemGroupKeyShouldAppendBundleToKey(): void
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
            $groupKeyBefore . ProductBundleCartItemGroupKeyExpander::GROUP_KEY_DELIMITER . $itemTransfer->getRelatedBundleItemIdentifier() . '1',
            $itemTransfer->getGroupKey()
        );
    }

    /**
     * @return void
     */
    public function testPostSaveCartUpdateBundlesShouldReturnQuoteWithoutBundlesWhenBundleIsRemoved(): void
    {
        $quoteTransfer = $this->createBaseQuoteTransfer();

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
    public function testPreCheckCartActiveWhenBundleProductIsActive(): void
    {
        $productConcreteTransfer = $this->createProductBundle(static::BUNDLED_PRODUCT_PRICE_2, true);

        $productBundleFacade = $this->createProductBundleFacade();

        $cartChangeTransfer = new CartChangeTransfer();

        $itemTransfer = new ItemTransfer();
        $itemTransfer->setSku($productConcreteTransfer->getSku());

        $cartChangeTransfer->addItem($itemTransfer);

        $cartChangeTransfer = $productBundleFacade->preCheckCartActive($cartChangeTransfer);

        $this->assertTrue($cartChangeTransfer->getIsSuccess());
        $this->assertEmpty($cartChangeTransfer->getMessages());
    }

    /**
     * @return void
     */
    public function testPreCheckCartActiveWhenBundleProductIsNotActive(): void
    {
        $productConcreteTransfer = $this->createProductBundle(static::BUNDLED_PRODUCT_PRICE_2, false);

        $productBundleFacade = $this->createProductBundleFacade();

        $cartChangeTransfer = new CartChangeTransfer();

        $itemTransfer = new ItemTransfer();
        $itemTransfer->setSku($productConcreteTransfer->getSku());

        $cartChangeTransfer->addItem($itemTransfer);

        $cartChangeTransfer = $productBundleFacade->preCheckCartActive($cartChangeTransfer);

        $this->assertFalse($cartChangeTransfer->getIsSuccess());
        $this->assertNotEmpty($cartChangeTransfer->getMessages());
    }

    /**
     * @return void
     */
    public function testPreCheckCartAvailabilityWhenBundleAvailable(): void
    {
        $productConcreteTransfer = $this->createProductBundle(static::BUNDLED_PRODUCT_PRICE_2, true);

        $productBundleFacade = $this->createProductBundleFacade();

        $cartChangeTransfer = new CartChangeTransfer();

        $itemTransfer = new ItemTransfer();
        $itemTransfer->setSku($productConcreteTransfer->getSku());

        $quoteTransfer = $this->createBaseQuoteTransfer();
        $quoteTransfer->addItem($itemTransfer);

        $cartChangeTransfer->setQuote($quoteTransfer);

        $itemTransfer = new ItemTransfer();
        $itemTransfer->setSku($productConcreteTransfer->getSku());
        $itemTransfer->setQuantity(static::ID_STORE);

        $cartChangeTransfer->addItem($itemTransfer);

        $cartChangeTransfer = $productBundleFacade->preCheckCartAvailability($cartChangeTransfer);

        $this->assertTrue($cartChangeTransfer->getIsSuccess());
    }

    /**
     * @return void
     */
    public function testPreCheckCartAvailabilityWhenBundleUnavailable(): void
    {
        $productConcreteTransfer = $this->createProductBundle(static::BUNDLED_PRODUCT_PRICE_2);

        $productBundleFacade = $this->createProductBundleFacade();

        $cartChangeTransfer = new CartChangeTransfer();

        $itemTransfer = new ItemTransfer();
        $itemTransfer->setQuantity(5);
        $itemTransfer->setSku($productConcreteTransfer->getSku());

        $quoteTransfer = $this->createBaseQuoteTransfer();
        $quoteTransfer->addItem($itemTransfer);

        $cartChangeTransfer->setQuote($quoteTransfer);

        $itemTransfer = new ItemTransfer();
        $itemTransfer->setSku(static::SKU_BUNDLED_1);
        $itemTransfer->setQuantity(25);

        $cartChangeTransfer->addItem($itemTransfer);

        $cartChangeTransfer = $productBundleFacade->preCheckCartAvailability($cartChangeTransfer);

        $this->assertFalse($cartChangeTransfer->getIsSuccess());
    }

    /**
     * @return void
     */
    public function testPreCheckCheckoutAvailabilityWhenBundleUnavailable(): void
    {
        $productConcreteTransfer = $this->createProductBundle(static::BUNDLED_PRODUCT_PRICE_2);

        $productBundleFacade = $this->createProductBundleFacade();

        $quoteTransfer = $this->createBaseQuoteTransfer();

        $itemTransfer = new ItemTransfer();
        $itemTransfer->setQuantity(50);
        $itemTransfer->setSku(static::SKU_BUNDLED_1);

        $quoteTransfer->addItem($itemTransfer);

        $itemTransfer = new ItemTransfer();
        $itemTransfer->setQuantity(static::ID_STORE);
        $itemTransfer->setSku($productConcreteTransfer->getSku());
        $quoteTransfer->addBundleItem($itemTransfer);

        $checkoutResponseTransfer = new CheckoutResponseTransfer();

        $productBundleFacade->preCheckCheckoutAvailability($quoteTransfer, $checkoutResponseTransfer);

        $this->assertFalse($checkoutResponseTransfer->getIsSuccess());
    }

    /**
     * @return void
     */
    public function testPreCheckCheckoutAvailabilityWhenBundleAvailable(): void
    {
        $productConcreteTransfer = $this->createProductBundle(static::BUNDLED_PRODUCT_PRICE_2, true);

        $productBundleFacade = $this->createProductBundleFacade();

        $quoteTransfer = $this->createBaseQuoteTransfer();

        $itemTransfer = new ItemTransfer();
        $itemTransfer->setQuantity(static::ID_STORE);
        $itemTransfer->setSku(static::SKU_BUNDLED_1);

        $quoteTransfer->addItem($itemTransfer);

        $itemTransfer = new ItemTransfer();
        $itemTransfer->setQuantity(static::ID_STORE);
        $itemTransfer->setSku($productConcreteTransfer->getSku());
        $quoteTransfer->addBundleItem($itemTransfer);

        $checkoutResponseTransfer = new CheckoutResponseTransfer();

        $productBundleFacade->preCheckCheckoutAvailability($quoteTransfer, $checkoutResponseTransfer);

        $this->assertNull($checkoutResponseTransfer->getIsSuccess());
    }

    /**
     * @return void
     */
    public function testUpdateAffectedBundleAvailabilityWhenOneOfBundledItemsUnavailable(): void
    {
        $this->createProductBundle(static::BUNDLED_PRODUCT_PRICE_2);
        $storeTransfer = (new StoreTransfer())->setIdStore(static::ID_STORE);

        $this->tester->haveAvailabilityConcrete(static::SKU_BUNDLED_2, $storeTransfer, 0);

        $this->createProductBundleFacade()
            ->updateAffectedBundlesAvailability(static::SKU_BUNDLED_2);

        $bundledProductAvailability = $this->createAvailabilityFacade()
            ->findProductConcreteAvailabilityBySkuForStore(
                static::BUNDLE_SKU_3,
                $storeTransfer
            );

        $this->assertEquals(0, $bundledProductAvailability->getAvailability()->toString());
    }

    /**
     * @return void
     */
    public function testSaveBundledProductsShouldAddProvidedConcreteToBundle(): void
    {
        $productConcreteBundleTransfer = $this->createProduct(static::BUNDLED_PRODUCT_PRICE_1, static::BUNDLE_SKU_3);
        $productConcreteToAssignTransfer = $this->createProduct(static::BUNDLED_PRODUCT_PRICE_1, static::SKU_BUNDLED_1);

        $productBundleFacade = $this->createProductBundleFacade();

        $productBundleTransfer = new ProductBundleTransfer();

        $bundledProductTransfer = new ProductForBundleTransfer();
        $bundledProductTransfer->setIdProductConcrete($productConcreteToAssignTransfer->getIdProductConcrete());
        $bundledProductTransfer->setIdProductBundle($productConcreteBundleTransfer->getIdProductConcrete());
        $bundledProductTransfer->setQuantity(2);

        $productBundleTransfer->addBundledProduct($bundledProductTransfer);

        $productConcreteBundleTransfer->setProductBundle($productBundleTransfer);

        $productConcreteBundleTransfer = $productBundleFacade->saveBundledProducts($productConcreteBundleTransfer);

        $bundledProducts = SpyProductBundleQuery::create()->findByFkProduct($productConcreteBundleTransfer->getIdProductConcrete());

        $this->assertCount(static::ID_STORE, $bundledProducts);

        $bundledProductEntity = $bundledProducts[0];

        $this->assertSame($bundledProductTransfer->getQuantity(), $bundledProductEntity->getQuantity());
        $this->assertSame($productConcreteToAssignTransfer->getIdProductConcrete(), $bundledProductEntity->getFkBundledProduct());
        $this->assertSame($productConcreteBundleTransfer->getIdProductConcrete(), $bundledProductEntity->getFkProduct());
    }

    /**
     * @return void
     */
    public function testSaveBundledProductsWhenRemoveListProvidedShouldRemoveBundledProducts(): void
    {
        $this->markTestIncomplete('Something with transactions');
        $productConcreteBundleTransfer = $this->createProductBundle(static::BUNDLED_PRODUCT_PRICE_2);

        $productBundleFacade = $this->createProductBundleFacade();

        $productBundleTransfer = $productConcreteBundleTransfer->getProductBundle();
        $bundledProducts = $productBundleTransfer->getBundledProducts();
        $productBundleTransfer->setBundlesToRemove([$bundledProducts[0]->getIdProductConcrete()]);
        $productConcreteBundleTransfer->setProductBundle($productBundleTransfer);

        $productConcreteBundleTransfer = $productBundleFacade->saveBundledProducts($productConcreteBundleTransfer);

        $bundledProducts = SpyProductBundleQuery::create()->findByFkProduct($productConcreteBundleTransfer->getIdProductConcrete());

        $this->assertCount(static::ID_STORE, $bundledProducts);
    }

    /**
     * @return void
     */
    public function testFindBundledProductsByIdProductConcreteShouldReturnPersistedBundledProducts(): void
    {
        $this->markTestIncomplete('Something with transactions');
        $productConcreteBundleTransfer = $this->createProductBundle(static::BUNDLED_PRODUCT_PRICE_2);

        $productBundleFacade = $this->createProductBundleFacade();
        $bundledProducts = $productBundleFacade->findBundledProductsByIdProductConcrete(
            $productConcreteBundleTransfer->getIdProductConcrete()
        );

        $this->assertCount(2, $bundledProducts);
    }

    /**
     * @return void
     */
    public function testAssignBundledProductsToProductConcreteShouldAssignPersistedBundledProducts(): void
    {
        $this->markTestIncomplete('Something with transactions');
        $productConcreteBundleTransfer = $this->createProductBundle(static::BUNDLED_PRODUCT_PRICE_2);

        $productConcreteTransfer = new ProductConcreteTransfer();
        $productConcreteTransfer->setIdProductConcrete($productConcreteBundleTransfer->getIdProductConcrete());

        $productBundleFacade = $this->createProductBundleFacade();
        $productConcreteTransfer = $productBundleFacade->assignBundledProductsToProductConcrete($productConcreteTransfer);

        $this->assertNotNull($productConcreteTransfer->getProductBundle());
        $this->assertCount(2, $productConcreteTransfer->getProductBundle()->getBundledProducts());
    }

    /**
     * @return void
     */
    public function testFilterBundleItemsOnCartReloadShouldRemoveBundleItems(): void
    {
        $productBundleFacade = $this->createProductBundleFacade();

        $quoteTransfer = (new QuoteBuilder())
            ->withBundleItem([
                ItemTransfer::BUNDLE_ITEM_IDENTIFIER => static::ID_STORE,
            ])->withItem([
                ItemTransfer::RELATED_BUNDLE_ITEM_IDENTIFIER => static::ID_STORE,
            ])->withAnotherItem([
                ItemTransfer::SKU => '123',
            ])
            ->build();

        $updatedQuoteTransfer = $productBundleFacade->filterBundleItemsOnCartReload($quoteTransfer);

        $this->assertCount(0, $updatedQuoteTransfer->getBundleItems());
        $this->assertCount(2, $updatedQuoteTransfer->getItems());
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Business\ProductBundleFacadeInterface
     */
    protected function createProductBundleFacade(): ProductBundleFacadeInterface
    {
        return new ProductBundleFacade();
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Dependency\Facade\ProductBundleToAvailabilityFacadeInterface
     */
    protected function createAvailabilityFacade(): ProductBundleToAvailabilityFacadeInterface
    {
        return new ProductBundleToAvailabilityFacadeBridge($this->tester->getLocator()->availability()->facade());
    }

    /**
     * @param int $price
     * @param string $sku
     * @param bool $isAlwaysAvailable
     *
     * @return \Generated\Shared\Transfer\ProductConcreteTransfer
     */
    protected function createProduct($price, $sku, $isAlwaysAvailable = false): ProductConcreteTransfer
    {
        $currencyTransfer = $this->tester->haveCurrencyTransfer([CurrencyTransfer::CODE => 'EUR']);
        $productConcreteTransfer = $this->tester->haveProduct([
            ProductConcreteTransfer::SKU => $sku,
            ProductConcreteTransfer::IS_ACTIVE => $isAlwaysAvailable,
        ]);

        $this->tester->haveProductInStock([
            StockProductTransfer::SKU => $productConcreteTransfer->getSku(),
            StockProductTransfer::QUANTITY => 10,
            StockProductTransfer::IS_NEVER_OUT_OF_STOCK => $isAlwaysAvailable,
        ]);

        $this->tester->havePriceProduct([
            PriceProductTransfer::SKU_PRODUCT_ABSTRACT => $productConcreteTransfer->getAbstractSku(),
            PriceProductTransfer::MONEY_VALUE => [
                MoneyValueTransfer::NET_AMOUNT => $price,
                MoneyValueTransfer::GROSS_AMOUNT => $price,
                MoneyValueTransfer::CURRENCY => $currencyTransfer,
            ],
        ]);

        return $productConcreteTransfer;
    }

    /**
     * @param int $bundlePrice
     * @param bool $isAlwaysAvailable
     *
     * @return \Generated\Shared\Transfer\ProductConcreteTransfer
     */
    protected function createProductBundle($bundlePrice, $isAlwaysAvailable = false): ProductConcreteTransfer
    {
        $productConcreteTransferToAssign1 = $this->createProduct(static::BUNDLED_PRODUCT_PRICE_1, static::SKU_BUNDLED_1, $isAlwaysAvailable);
        $productConcreteTransferToAssign2 = $this->createProduct(static::BUNDLED_PRODUCT_PRICE_2, static::SKU_BUNDLED_2, $isAlwaysAvailable);

        $productBundleTransfer = new ProductBundleTransfer();
        if ($isAlwaysAvailable) {
            $productBundleTransfer->setIsNeverOutOfStock(true);
        }

        $bundledProductTransfer = new ProductForBundleTransfer();
        $bundledProductTransfer->setQuantity(1);
        $bundledProductTransfer->setIdProductConcrete($productConcreteTransferToAssign1->getIdProductConcrete());
        $productBundleTransfer->addBundledProduct($bundledProductTransfer);

        $bundledProductTransfer = new ProductForBundleTransfer();
        $bundledProductTransfer->setQuantity(2);
        $bundledProductTransfer->setIdProductConcrete($productConcreteTransferToAssign2->getIdProductConcrete());
        $productBundleTransfer->addBundledProduct($bundledProductTransfer);

        $productConcreteTransfer = $this->createProduct($bundlePrice, static::BUNDLE_SKU_3, $isAlwaysAvailable);
        $productConcreteTransfer->setProductBundle($productBundleTransfer);

        $productBundleFacade = $this->createProductBundleFacade();
        $productBundleFacade->saveBundledProducts($productConcreteTransfer);

        return $productConcreteTransfer;
    }

    /**
     * @return \Generated\Shared\Transfer\QuoteTransfer
     */
    protected function createBaseQuoteTransfer(): QuoteTransfer
    {
        $storeTransfer = (new StoreTransfer())->setName('DE');

        return (new QuoteTransfer())
            ->setStore($storeTransfer);
    }
}
