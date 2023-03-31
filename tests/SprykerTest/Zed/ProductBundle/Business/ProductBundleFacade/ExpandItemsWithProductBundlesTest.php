<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\ProductBundle\Business\ProductBundleFacade;

use Codeception\Test\Unit;
use Generated\Shared\DataBuilder\CustomerBuilder;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Zed\Store\StoreDependencyProvider;
use SprykerTest\Zed\Sales\Helper\BusinessHelper;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group ProductBundle
 * @group Business
 * @group ProductBundleFacade
 * @group ExpandItemsWithProductBundlesTest
 * Add your own group annotations below this line
 */
class ExpandItemsWithProductBundlesTest extends Unit
{
    /**
     * @var string
     */
    protected const STORE_NAME_DE = 'DE';

    /**
     * @var string
     */
    protected const SERVICE_CURRENCY = 'currency';

    /**
     * @var string
     */
    protected const SERVICE_LOCALE = 'locale';

    /**
     * @var string
     */
    protected const DEFAULT_LOCALE = 'en_US';

    /**
     * @var string
     */
    protected const CUSTOMER_REFERENCE = 'CUSTOMER_REFERENCE';

    /**
     * @var string
     */
    protected const PRODUCT_CONCRETE_SKU_1 = 'PRODUCT_CONCRETE_SKU_1';

    /**
     * @var string
     */
    protected const PRODUCT_CONCRETE_SKU_2 = 'PRODUCT_CONCRETE_SKU_2';

    /**
     * @var string
     */
    protected const PRODUCT_CONCRETE_SKU_3 = 'PRODUCT_CONCRETE_SKU_3';

    /**
     * @var string
     */
    protected const BUNDLE_SKU_1 = 'BUNDLE_SKU_1';

    /**
     * @var string
     */
    protected const CURRENCY_ISO_CODE = 'CODE';

    /**
     * @var string
     */
    protected const EUR_ISO_CODE = 'EUR';

    /**
     * @var \SprykerTest\Zed\ProductBundle\ProductBundleBusinessTester
     */
    protected $tester;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->tester->configureTestStateMachine([BusinessHelper::DEFAULT_OMS_PROCESS_NAME]);

        $this->tester->setDependency(StoreDependencyProvider::STORE_CURRENT, static::STORE_NAME_DE);
        $container = $this->tester->getContainer();
        $container->set(static::SERVICE_CURRENCY, static::EUR_ISO_CODE);
        $container->set(static::SERVICE_LOCALE, static::DEFAULT_LOCALE);
    }

    /**
     * @return void
     */
    public function testExpandBundleItemsWithProductBundles(): void
    {
        // Arrange
        $productConcreteTransfer = $this->tester->createProduct(100, static::PRODUCT_CONCRETE_SKU_1, true, true);
        $bundleTransfer = $this->tester->createProductBundle(100, true, true, [$productConcreteTransfer]);
        $customerTransfer = (new CustomerBuilder([CustomerTransfer::CUSTOMER_REFERENCE => static::CUSTOMER_REFERENCE]))->build();
        $quoteSeed = [
            QuoteTransfer::CUSTOMER_REFERENCE => static::CUSTOMER_REFERENCE,
            QuoteTransfer::CUSTOMER => $customerTransfer,
        ];
        $itemSeed = [
            ItemTransfer::SKU => $productConcreteTransfer->getSku(),
            ItemTransfer::RELATED_BUNDLE_ITEM_IDENTIFIER => $bundleTransfer->getSku(),
            ItemTransfer::QUANTITY => 1,
            ItemTransfer::UNIT_PRICE => 1,
        ];
        $bundleItemSeed = [
            ItemTransfer::SKU => $bundleTransfer->getSku(),
            ItemTransfer::BUNDLE_ITEM_IDENTIFIER => $bundleTransfer->getSku(),
            ItemTransfer::QUANTITY => 1,
            ItemTransfer::UNIT_PRICE => 1,
        ];
        $quoteTransfer = $this->tester->buildQuote($quoteSeed, $itemSeed, $bundleItemSeed);
        $orderTransfer = $this->tester->createOrderFromQuote($quoteTransfer);

        // Act
        $itemTransfers = $this->tester->getFacade()->expandItemsWithProductBundles($orderTransfer->getItems()->getArrayCopy());

        // Assert
        $this->assertNotEmpty($itemTransfers[0]->getProductBundle());
        $this->assertSame($itemTransfers[0]->getRelatedBundleItemIdentifier(), $itemTransfers[0]->getProductBundle()->getBundleItemIdentifier());
        $this->assertEquals($bundleTransfer->getIdProductConcrete(), $itemTransfers[0]->getProductBundle()->getId());
        $this->assertEquals($bundleTransfer->getFkProductAbstract(), $itemTransfers[0]->getProductBundle()->getIdProductAbstract());
    }

    /**
     * @return void
     */
    public function testExpandBundleItemsWithoutProductBundles(): void
    {
        // Arrange
        $productConcreteTransfer = $this->tester->createProduct(100, static::PRODUCT_CONCRETE_SKU_1, true, true);
        $customerTransfer = (new CustomerBuilder([CustomerTransfer::CUSTOMER_REFERENCE => static::CUSTOMER_REFERENCE]))->build();
        $quoteSeed = [
            QuoteTransfer::CUSTOMER_REFERENCE => static::CUSTOMER_REFERENCE,
            QuoteTransfer::CUSTOMER => $customerTransfer,
        ];
        $itemSeed = [
            ItemTransfer::SKU => $productConcreteTransfer->getSku(),
            ItemTransfer::QUANTITY => 1,
            ItemTransfer::UNIT_PRICE => 1,
        ];
        $quoteTransfer = $this->tester->buildQuote($quoteSeed, $itemSeed);
        $orderTransfer = $this->tester->createOrderFromQuote($quoteTransfer);

        // Act
        $itemTransfers = $this->tester->getFacade()->expandItemsWithProductBundles($orderTransfer->getItems()->getArrayCopy());

        // Assert
        $this->assertEmpty($itemTransfers[0]->getProductBundle());
    }
}
