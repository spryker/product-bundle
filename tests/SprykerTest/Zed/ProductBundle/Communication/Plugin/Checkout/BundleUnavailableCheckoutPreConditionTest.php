<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\ProductBundle\Communication\Plugin\Checkout;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CheckoutResponseTransfer;
use Generated\Shared\Transfer\OriginalSalesOrderItemTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Shared\ProductApproval\ProductApprovalConfig as SharedProductApprovalConfig;
use Spryker\Zed\Availability\Communication\Plugin\ProductsAvailableCheckoutPreConditionPlugin;
use Spryker\Zed\Locale\LocaleDependencyProvider;
use Spryker\Zed\ProductApproval\Communication\Plugin\Checkout\ProductApprovalCheckoutPreConditionPlugin;
use Spryker\Zed\ProductBundle\Communication\Plugin\Checkout\OrderAmendmentProductBundleStoreCheckoutPreConditionPlugin;
use Spryker\Zed\ProductBundle\Communication\Plugin\Checkout\ProductBundleAvailabilityCheckoutPreConditionPlugin;
use Spryker\Zed\ProductBundle\Communication\Plugin\Checkout\ProductBundleStoreCheckoutPreConditionPlugin;
use Spryker\Zed\ProductCartConnector\Communication\Plugin\Checkout\ProductExistsCheckoutPreConditionPlugin;
use SprykerTest\Zed\ProductBundle\ProductBundleCommunicationTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group ProductBundle
 * @group Communication
 * @group Plugin
 * @group Checkout
 * @group BundleUnavailableCheckoutPreConditionTest
 * Add your own group annotations below this line
 */
class BundleUnavailableCheckoutPreConditionTest extends Unit
{
    protected const string LOCALE_NAME_EN = 'en_US';

    protected const string GLOSSARY_KEY_PRODUCT_NOT_APPROVED = 'product-approval.message.not-approved';

    protected const string GLOSSARY_KEY_BUNDLE_ITEM_UNAVAILABLE = 'product_bundle.unavailable';

    protected const string GLOSSARY_KEY_PRODUCT_UNAVAILABLE = 'product.unavailable';

    protected const int BUNDLED_PRODUCT_AVAILABILITY_NONE = 0;

    protected const int BUNDLE_QUANTITY_IN_CART_SEVERAL = 2;

    protected ProductBundleCommunicationTester $tester;

    protected function _before(): void
    {
        parent::_before();

        $this->tester->setDependency(LocaleDependencyProvider::LOCALE_CURRENT, static::LOCALE_NAME_EN);
    }

    public function testCheckoutIsNotBlockedWhenBundleAndBundledProductsAreValid(): void
    {
        // Arrange
        $bundleProductConcreteTransfer = $this->tester->haveAvailableProductBundle();
        $quoteTransfer = $this->tester->haveQuoteWithProductBundleInCart($bundleProductConcreteTransfer);

        // Act
        $checkoutResponseTransfer = $this->runCheckoutPreConditions($quoteTransfer);

        // Assert
        $this->assertCheckoutIsNotBlocked($checkoutResponseTransfer);
    }

    public function testCheckoutIsBlockedWhenBundleApprovalStatusIsRemoved(): void
    {
        // Arrange
        $bundleProductConcreteTransfer = $this->tester->haveAvailableProductBundle();
        $quoteTransfer = $this->tester->haveQuoteWithProductBundleInCart($bundleProductConcreteTransfer);
        $this->assertSummaryPageIsPlaceable($quoteTransfer);

        $this->tester->setApprovalStatus(
            $bundleProductConcreteTransfer->getFkProductAbstractOrFail(),
            SharedProductApprovalConfig::STATUS_DRAFT,
        );

        // Act
        $checkoutResponseTransfer = $this->runCheckoutPreConditions($quoteTransfer);

        // Assert
        $this->assertCheckoutIsBlocked(
            $checkoutResponseTransfer,
            static::GLOSSARY_KEY_PRODUCT_NOT_APPROVED,
            'bundle approval status removed',
        );
    }

    public function testCheckoutIsBlockedWhenBundleIsDeassignedFromStore(): void
    {
        // Arrange
        $bundleProductConcreteTransfer = $this->tester->haveAvailableProductBundle();
        $quoteTransfer = $this->tester->haveQuoteWithProductBundleInCart($bundleProductConcreteTransfer);
        $this->assertSummaryPageIsPlaceable($quoteTransfer);

        $this->tester->deassignProductAbstractFromAllStores(
            $bundleProductConcreteTransfer->getFkProductAbstractOrFail(),
        );

        // Act
        $checkoutResponseTransfer = $this->runCheckoutPreConditions($quoteTransfer);

        // Assert
        $this->assertCheckoutIsBlocked(
            $checkoutResponseTransfer,
            static::GLOSSARY_KEY_PRODUCT_UNAVAILABLE,
            'whole bundle deassigned from store',
        );
    }

    public function testCheckoutIsBlockedWhenBundledProductIsInactive(): void
    {
        // Arrange
        $bundleProductConcreteTransfer = $this->tester->haveAvailableProductBundle();
        $quoteTransfer = $this->tester->haveQuoteWithProductBundleInCart($bundleProductConcreteTransfer);
        $this->assertSummaryPageIsPlaceable($quoteTransfer);

        $this->tester->deactivateProductConcrete(
            $this->tester->getFirstBundledProductConcreteTransfer()->getSkuOrFail(),
        );

        // Act
        $checkoutResponseTransfer = $this->runCheckoutPreConditions($quoteTransfer);

        // Assert
        $this->assertCheckoutIsBlocked(
            $checkoutResponseTransfer,
            static::GLOSSARY_KEY_BUNDLE_ITEM_UNAVAILABLE,
            'bundled product made inactive',
        );
    }

    public function testCheckoutIsBlockedWhenBundledProductIsDeassignedFromStore(): void
    {
        // Arrange
        $bundleProductConcreteTransfer = $this->tester->haveAvailableProductBundle();
        $quoteTransfer = $this->tester->haveQuoteWithProductBundleInCart($bundleProductConcreteTransfer);
        $this->assertSummaryPageIsPlaceable($quoteTransfer);

        $this->tester->deassignProductAbstractFromAllStores(
            $this->tester->getFirstBundledProductConcreteTransfer()->getFkProductAbstractOrFail(),
        );

        // Act
        $checkoutResponseTransfer = $this->runCheckoutPreConditions($quoteTransfer);

        // Assert
        $this->assertCheckoutIsBlocked(
            $checkoutResponseTransfer,
            static::GLOSSARY_KEY_BUNDLE_ITEM_UNAVAILABLE,
            'one bundled product deassigned from store',
        );
    }

    public function testCheckoutIsBlockedWhenBundledProductIsOutOfStock(): void
    {
        // Arrange
        $bundleProductConcreteTransfer = $this->tester->haveAvailableProductBundle();
        $quoteTransfer = $this->tester->haveQuoteWithProductBundleInCart($bundleProductConcreteTransfer);
        $this->assertSummaryPageIsPlaceable($quoteTransfer);

        $this->tester->setProductConcreteAvailability(
            $this->tester->getFirstBundledProductConcreteTransfer()->getSkuOrFail(),
            static::BUNDLED_PRODUCT_AVAILABILITY_NONE,
        );

        // Act
        $checkoutResponseTransfer = $this->runCheckoutPreConditions($quoteTransfer);

        // Assert
        $this->assertCheckoutIsBlocked(
            $checkoutResponseTransfer,
            static::GLOSSARY_KEY_BUNDLE_ITEM_UNAVAILABLE,
            'bundled product ran out of stock',
        );
    }

    public function testCheckoutReportsBundleOnlyOnceWhenSeveralUnitsOfTheSameBundleAreInCart(): void
    {
        // Arrange
        $bundleProductConcreteTransfer = $this->tester->haveAvailableProductBundle();
        $quoteTransfer = $this->tester->haveQuoteWithProductBundleInCart(
            $bundleProductConcreteTransfer,
            static::BUNDLE_QUANTITY_IN_CART_SEVERAL,
        );
        $this->assertSummaryPageIsPlaceable($quoteTransfer);

        $this->tester->deassignProductAbstractFromAllStores(
            $bundleProductConcreteTransfer->getFkProductAbstractOrFail(),
        );

        // Act
        $checkoutResponseTransfer = $this->runCheckoutPreConditions($quoteTransfer);

        // Assert
        $this->assertCheckoutIsBlocked(
            $checkoutResponseTransfer,
            static::GLOSSARY_KEY_PRODUCT_UNAVAILABLE,
            'whole bundle deassigned from store',
        );
        $this->assertCheckoutErrorCount(
            $checkoutResponseTransfer,
            static::GLOSSARY_KEY_PRODUCT_UNAVAILABLE,
            1,
        );
    }

    public function testOrderAmendmentIsNotBlockedWhenDeassignedBundleIsPartOfTheOriginalOrder(): void
    {
        // Arrange
        $bundleProductConcreteTransfer = $this->tester->haveAvailableProductBundle();
        $quoteTransfer = $this->tester->haveQuoteWithProductBundleInCart($bundleProductConcreteTransfer);
        $quoteTransfer->addOriginalSalesOrderItem(
            (new OriginalSalesOrderItemTransfer())->setSku($bundleProductConcreteTransfer->getSkuOrFail()),
        );

        $this->tester->deassignProductAbstractFromAllStores(
            $bundleProductConcreteTransfer->getFkProductAbstractOrFail(),
        );

        // Act
        $checkoutResponseTransfer = new CheckoutResponseTransfer();
        (new OrderAmendmentProductBundleStoreCheckoutPreConditionPlugin())
            ->checkCondition($quoteTransfer, $checkoutResponseTransfer);

        // Assert
        $this->assertCheckoutIsNotBlocked($checkoutResponseTransfer);
    }

    public function testOrderAmendmentIsBlockedWhenDeassignedBundleIsNotPartOfTheOriginalOrder(): void
    {
        // Arrange
        $bundleProductConcreteTransfer = $this->tester->haveAvailableProductBundle();
        $quoteTransfer = $this->tester->haveQuoteWithProductBundleInCart($bundleProductConcreteTransfer);

        $this->tester->deassignProductAbstractFromAllStores(
            $bundleProductConcreteTransfer->getFkProductAbstractOrFail(),
        );

        // Act
        $checkoutResponseTransfer = new CheckoutResponseTransfer();
        (new OrderAmendmentProductBundleStoreCheckoutPreConditionPlugin())
            ->checkCondition($quoteTransfer, $checkoutResponseTransfer);

        // Assert
        $this->assertCheckoutIsBlocked(
            $checkoutResponseTransfer,
            static::GLOSSARY_KEY_PRODUCT_UNAVAILABLE,
            'newly added bundle deassigned from store during order amendment',
        );
    }

    protected function runCheckoutPreConditions(QuoteTransfer $quoteTransfer): CheckoutResponseTransfer
    {
        $checkoutResponseTransfer = new CheckoutResponseTransfer();

        foreach ($this->createCheckoutPreConditionPlugins() as $checkoutPreConditionPlugin) {
            $checkoutPreConditionPlugin->checkCondition($quoteTransfer, $checkoutResponseTransfer);
        }

        return $checkoutResponseTransfer;
    }

    /**
     * @see \Pyz\Zed\Checkout\CheckoutDependencyProvider::getCheckoutPreConditions()
     *
     * @return list<\Spryker\Zed\Checkout\Dependency\Plugin\CheckoutPreConditionInterface|\Spryker\Zed\CheckoutExtension\Dependency\Plugin\CheckoutPreConditionPluginInterface>
     */
    protected function createCheckoutPreConditionPlugins(): array
    {
        return [
            new ProductsAvailableCheckoutPreConditionPlugin(),
            new ProductBundleAvailabilityCheckoutPreConditionPlugin(),
            new ProductBundleStoreCheckoutPreConditionPlugin(),
            new ProductExistsCheckoutPreConditionPlugin(),
            new ProductApprovalCheckoutPreConditionPlugin(),
        ];
    }

    protected function assertSummaryPageIsPlaceable(QuoteTransfer $quoteTransfer): void
    {
        $this->assertCount(
            0,
            $this->runCheckoutPreConditions($quoteTransfer)->getErrors(),
            'The bundle was expected to be placeable before it got invalidated, but the checkout pre-conditions already rejected it.',
        );
    }

    protected function assertCheckoutIsNotBlocked(CheckoutResponseTransfer $checkoutResponseTransfer): void
    {
        $this->assertCount(
            0,
            $checkoutResponseTransfer->getErrors(),
            'Expected the order to be placeable, got: ' . $this->formatCheckoutErrors($checkoutResponseTransfer),
        );
    }

    protected function assertCheckoutIsBlocked(
        CheckoutResponseTransfer $checkoutResponseTransfer,
        string $expectedGlossaryKey,
        string $scenario
    ): void {
        $this->assertGreaterThan(
            0,
            $checkoutResponseTransfer->getErrors()->count(),
            'Expected the order to be blocked after "' . $scenario . '", but no checkout error was raised.',
        );

        $this->assertContains(
            $expectedGlossaryKey,
            $this->getCheckoutErrorMessages($checkoutResponseTransfer),
            'Expected "' . $expectedGlossaryKey . '" to be raised after "' . $scenario . '", got: '
                . $this->formatCheckoutErrors($checkoutResponseTransfer),
        );
    }

    protected function assertCheckoutErrorCount(
        CheckoutResponseTransfer $checkoutResponseTransfer,
        string $glossaryKey,
        int $expectedCount
    ): void {
        $matchingCheckoutErrorCount = 0;

        foreach ($checkoutResponseTransfer->getErrors() as $checkoutErrorTransfer) {
            if ($checkoutErrorTransfer->getMessage() === $glossaryKey) {
                $matchingCheckoutErrorCount++;
            }
        }

        $this->assertSame(
            $expectedCount,
            $matchingCheckoutErrorCount,
            'Expected "' . $glossaryKey . '" to be raised ' . $expectedCount . ' time(s), got: '
                . $this->formatCheckoutErrors($checkoutResponseTransfer),
        );
    }

    /**
     * @return list<string>
     */
    protected function getCheckoutErrorMessages(CheckoutResponseTransfer $checkoutResponseTransfer): array
    {
        $messages = [];

        foreach ($checkoutResponseTransfer->getErrors() as $checkoutErrorTransfer) {
            $messages[] = (string)$checkoutErrorTransfer->getMessage();
        }

        return $messages;
    }

    protected function formatCheckoutErrors(CheckoutResponseTransfer $checkoutResponseTransfer): string
    {
        $messages = $this->getCheckoutErrorMessages($checkoutResponseTransfer);

        return $messages === [] ? 'no errors' : implode(', ', $messages);
    }
}
