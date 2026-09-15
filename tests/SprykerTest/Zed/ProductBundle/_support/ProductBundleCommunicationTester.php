<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\ProductBundle;

use Codeception\Actor;
use Generated\Shared\Transfer\CartChangeTransfer;
use Generated\Shared\Transfer\CurrencyTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\MoneyValueTransfer;
use Generated\Shared\Transfer\PriceProductTransfer;
use Generated\Shared\Transfer\ProductBundleTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;
use Generated\Shared\Transfer\ProductForBundleTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\StockProductTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Orm\Zed\Product\Persistence\SpyProductAbstractQuery;
use Orm\Zed\Product\Persistence\SpyProductAbstractStoreQuery;
use Orm\Zed\Product\Persistence\SpyProductQuery;
use Spryker\Shared\Price\PriceConfig;
use Spryker\Shared\ProductApproval\ProductApprovalConfig as SharedProductApprovalConfig;

/**
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = null)
 * @method \Spryker\Zed\ProductBundle\Business\ProductBundleFacadeInterface getFacade()
 *
 * @SuppressWarnings(\SprykerTest\Zed\ProductBundle\PHPMD)
 */
class ProductBundleCommunicationTester extends Actor
{
    use _generated\ProductBundleCommunicationTesterActions;

    /**
     * @var string
     */
    protected const STORE_NAME_DE = 'DE';

    protected const string CURRENCY_CODE_EUR = 'EUR';

    protected const int BUNDLE_PRICE = 1000;

    protected const int BUNDLED_PRODUCT_PRICE = 300;

    protected const int BUNDLED_PRODUCT_STOCK_QUANTITY = 10;

    protected const int BUNDLED_PRODUCT_QUANTITY = 1;

    protected const int BUNDLE_QUANTITY_IN_CART = 1;

    protected const int BUNDLED_PRODUCT_COUNT = 2;

    /**
     * @var array<\Generated\Shared\Transfer\ProductConcreteTransfer>
     */
    protected array $bundledProductConcreteTransfers = [];

    public function createProductBundle(bool $isProductActive = false, string $sku = 'sku1'): ProductConcreteTransfer
    {
        $productForBundleTransfer = (new ProductForBundleTransfer())
            ->setQuantity(1)
            ->setIdProductConcrete($this->createProduct($sku, $isProductActive)->getIdProductConcrete());
        $productBundleTransfer = (new ProductBundleTransfer())
            ->setIsNeverOutOfStock(true)
            ->addBundledProduct($productForBundleTransfer);

        $productConcreteTransfer = $this->createProduct('sku2', $isProductActive);
        $productConcreteTransfer->setProductBundle($productBundleTransfer);

        $this->getFacade()->saveBundledProducts($productConcreteTransfer);

        return $productConcreteTransfer;
    }

    public function haveAvailableProductBundle(): ProductConcreteTransfer
    {
        $storeTransfer = $this->haveStore([StoreTransfer::NAME => static::STORE_NAME_DE]);
        $currencyTransfer = $this->haveCurrencyTransfer([CurrencyTransfer::CODE => static::CURRENCY_CODE_EUR]);

        $productBundleTransfer = new ProductBundleTransfer();
        $this->bundledProductConcreteTransfers = [];

        for ($i = 0; $i < static::BUNDLED_PRODUCT_COUNT; $i++) {
            $bundledProductConcreteTransfer = $this->haveSellableProductConcrete(
                $storeTransfer,
                $currencyTransfer,
                static::BUNDLED_PRODUCT_PRICE,
            );

            $this->bundledProductConcreteTransfers[] = $bundledProductConcreteTransfer;

            $productBundleTransfer->addBundledProduct(
                (new ProductForBundleTransfer())
                    ->setSku($bundledProductConcreteTransfer->getSkuOrFail())
                    ->setIdProductConcrete($bundledProductConcreteTransfer->getIdProductConcreteOrFail())
                    ->setQuantity(static::BUNDLED_PRODUCT_QUANTITY),
            );
        }

        $bundleProductConcreteTransfer = $this->haveSellableProductConcrete(
            $storeTransfer,
            $currencyTransfer,
            static::BUNDLE_PRICE,
        );
        $bundleProductConcreteTransfer->setProductBundle($productBundleTransfer);

        $this->getLocator()->productBundle()->facade()->saveBundledProducts($bundleProductConcreteTransfer);
        $this->clearAvailabilityHandlerCache();

        return $bundleProductConcreteTransfer;
    }

    public function haveQuoteWithProductBundleInCart(
        ProductConcreteTransfer $bundleProductConcreteTransfer,
        int $bundleQuantity = self::BUNDLE_QUANTITY_IN_CART
    ): QuoteTransfer {
        $storeTransfer = $this->haveStore([StoreTransfer::NAME => static::STORE_NAME_DE]);
        $currencyTransfer = $this->haveCurrencyTransfer([CurrencyTransfer::CODE => static::CURRENCY_CODE_EUR]);

        $quoteTransfer = (new QuoteTransfer())
            ->setStore($storeTransfer)
            ->setCurrency($currencyTransfer)
            ->setPriceMode(PriceConfig::PRICE_MODE_GROSS);

        $cartChangeTransfer = (new CartChangeTransfer())
            ->setQuote($quoteTransfer)
            ->addItem(
                (new ItemTransfer())
                    ->setId($bundleProductConcreteTransfer->getIdProductConcreteOrFail())
                    ->setSku($bundleProductConcreteTransfer->getSkuOrFail())
                    ->setQuantity($bundleQuantity)
                    ->setUnitGrossPrice(static::BUNDLE_PRICE)
                    ->setIdProductAbstract($bundleProductConcreteTransfer->getFkProductAbstractOrFail())
                    ->setAbstractSku($bundleProductConcreteTransfer->getAbstractSkuOrFail()),
            );

        $cartChangeTransfer = $this->getLocator()->productBundle()->facade()->expandBundleItems($cartChangeTransfer);

        foreach ($cartChangeTransfer->getItems() as $itemTransfer) {
            $quoteTransfer->addItem($itemTransfer);
        }

        return $quoteTransfer;
    }

    public function getFirstBundledProductConcreteTransfer(): ProductConcreteTransfer
    {
        return $this->bundledProductConcreteTransfers[0];
    }

    public function setProductConcreteAvailability(string $sku, int $quantity): void
    {
        $this->haveAvailabilityConcrete(
            $sku,
            $this->haveStore([StoreTransfer::NAME => static::STORE_NAME_DE]),
            $quantity,
        );
        $this->clearAvailabilityHandlerCache();
    }

    public function setApprovalStatus(int $idProductAbstract, string $approvalStatus): void
    {
        $productAbstractEntity = SpyProductAbstractQuery::create()
            ->filterByIdProductAbstract($idProductAbstract)
            ->findOne();

        $this->assertNotNull(
            $productAbstractEntity,
            'Expected the product abstract fixture #' . $idProductAbstract . ' to exist.',
        );

        $productAbstractEntity->setApprovalStatus($approvalStatus);
        $productAbstractEntity->save();
    }

    public function deassignProductAbstractFromAllStores(int $idProductAbstract): void
    {
        SpyProductAbstractStoreQuery::create()
            ->filterByFkProductAbstract($idProductAbstract)
            ->delete();
    }

    public function deactivateProductConcrete(string $sku): void
    {
        $productEntity = SpyProductQuery::create()
            ->filterBySku($sku)
            ->findOne();

        $this->assertNotNull(
            $productEntity,
            'Expected the product concrete fixture "' . $sku . '" to exist.',
        );

        $productEntity->setIsActive(false);
        $productEntity->save();
    }

    protected function haveSellableProductConcrete(
        StoreTransfer $storeTransfer,
        CurrencyTransfer $currencyTransfer,
        int $price
    ): ProductConcreteTransfer {
        $productConcreteTransfer = $this->haveProduct([
            ProductConcreteTransfer::IS_ACTIVE => true,
        ]);

        $this->setApprovalStatus(
            $productConcreteTransfer->getFkProductAbstractOrFail(),
            SharedProductApprovalConfig::STATUS_APPROVED,
        );
        $this->assignProductAbstractToStore(
            $productConcreteTransfer->getFkProductAbstractOrFail(),
            $storeTransfer->getIdStoreOrFail(),
        );

        $this->haveProductInStockForStore($storeTransfer, [
            StockProductTransfer::SKU => $productConcreteTransfer->getSkuOrFail(),
            StockProductTransfer::QUANTITY => static::BUNDLED_PRODUCT_STOCK_QUANTITY,
            StockProductTransfer::IS_NEVER_OUT_OF_STOCK => false,
        ]);
        $this->haveAvailabilityConcrete(
            $productConcreteTransfer->getSkuOrFail(),
            $storeTransfer,
            static::BUNDLED_PRODUCT_STOCK_QUANTITY,
        );

        $this->havePriceProduct([
            PriceProductTransfer::SKU_PRODUCT_ABSTRACT => $productConcreteTransfer->getAbstractSkuOrFail(),
            PriceProductTransfer::SKU_PRODUCT => $productConcreteTransfer->getSkuOrFail(),
            PriceProductTransfer::MONEY_VALUE => [
                MoneyValueTransfer::NET_AMOUNT => $price,
                MoneyValueTransfer::GROSS_AMOUNT => $price,
                MoneyValueTransfer::CURRENCY => $currencyTransfer,
                MoneyValueTransfer::FK_STORE => $storeTransfer->getIdStoreOrFail(),
            ],
        ]);

        return $productConcreteTransfer;
    }

    protected function assignProductAbstractToStore(int $idProductAbstract, int $idStore): void
    {
        $productAbstractStoreEntity = SpyProductAbstractStoreQuery::create()
            ->filterByFkProductAbstract($idProductAbstract)
            ->filterByFkStore($idStore)
            ->findOneOrCreate();

        $productAbstractStoreEntity->save();
    }

    protected function createProduct(
        string $sku,
        bool $isActive = false
    ): ProductConcreteTransfer {
        $productConcreteTransfer = $this->haveProduct([
            ProductConcreteTransfer::SKU => $sku,
            ProductConcreteTransfer::IS_ACTIVE => $isActive,
        ]);

        $storeTransfer = $this->haveStore([StoreTransfer::NAME => static::STORE_NAME_DE]);
        $this->haveProductInStockForStore($storeTransfer, [
            StockProductTransfer::SKU => $productConcreteTransfer->getSku(),
            StockProductTransfer::QUANTITY => 10,
            StockProductTransfer::IS_NEVER_OUT_OF_STOCK => true,
        ]);

        return $productConcreteTransfer;
    }
}
