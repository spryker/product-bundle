<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ProductBundle\Business;

use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;
use Spryker\Zed\ProductBundle\Business\ProductBundle\Availability\PreCheck\ProductBundleCartAvailabilityCheck;
use Spryker\Zed\ProductBundle\Business\ProductBundle\Availability\PreCheck\ProductBundleCheckoutAvailabilityCheck;
use Spryker\Zed\ProductBundle\Business\ProductBundle\Availability\ProductBundleAvailabilityHandler;
use Spryker\Zed\ProductBundle\Business\ProductBundle\Cache\ProductBundleCache;
use Spryker\Zed\ProductBundle\Business\ProductBundle\Cache\ProductBundleCacheInterface;
use Spryker\Zed\ProductBundle\Business\ProductBundle\Calculation\ProductBundlePriceCalculation;
use Spryker\Zed\ProductBundle\Business\ProductBundle\Cart\BundleItemRefresher;
use Spryker\Zed\ProductBundle\Business\ProductBundle\Cart\BundleItemUnfolder;
use Spryker\Zed\ProductBundle\Business\ProductBundle\Cart\BundleItemUnfolderInterface;
use Spryker\Zed\ProductBundle\Business\ProductBundle\Cart\BundleRefresherInterface;
use Spryker\Zed\ProductBundle\Business\ProductBundle\Cart\ProductBundleCartChangeObserver;
use Spryker\Zed\ProductBundle\Business\ProductBundle\Cart\ProductBundleCartChangeObserverInterface;
use Spryker\Zed\ProductBundle\Business\ProductBundle\Cart\ProductBundleCartExpander;
use Spryker\Zed\ProductBundle\Business\ProductBundle\Cart\ProductBundleCartItemGroupKeyExpander;
use Spryker\Zed\ProductBundle\Business\ProductBundle\Cart\ProductBundleCartPostSaveUpdate;
use Spryker\Zed\ProductBundle\Business\ProductBundle\Cart\ProductBundleImageCartExpander;
use Spryker\Zed\ProductBundle\Business\ProductBundle\Cart\ProductBundlePreReloadUpdater;
use Spryker\Zed\ProductBundle\Business\ProductBundle\CartNote\QuoteBundleItemsFinder;
use Spryker\Zed\ProductBundle\Business\ProductBundle\CartNote\QuoteBundleItemsFinderInterface;
use Spryker\Zed\ProductBundle\Business\ProductBundle\CartPriceCheck\ProductBundleCartPriceChecker;
use Spryker\Zed\ProductBundle\Business\ProductBundle\CartPriceCheck\ProductBundleCartPriceCheckerInterface;
use Spryker\Zed\ProductBundle\Business\ProductBundle\Checkout\ProductBundleOrderSaver;
use Spryker\Zed\ProductBundle\Business\ProductBundle\Expander\ProductBundleExpander;
use Spryker\Zed\ProductBundle\Business\ProductBundle\Expander\ProductBundleExpanderInterface;
use Spryker\Zed\ProductBundle\Business\ProductBundle\Expander\ProductBundleItemExpander;
use Spryker\Zed\ProductBundle\Business\ProductBundle\Expander\ProductBundleItemExpanderInterface;
use Spryker\Zed\ProductBundle\Business\ProductBundle\Expander\ProductOptionExpander;
use Spryker\Zed\ProductBundle\Business\ProductBundle\Expander\ProductOptionExpanderInterface;
use Spryker\Zed\ProductBundle\Business\ProductBundle\PersistentCart\BundleItemReplacer;
use Spryker\Zed\ProductBundle\Business\ProductBundle\PersistentCart\BundleItemReplacerInterface;
use Spryker\Zed\ProductBundle\Business\ProductBundle\PersistentCart\ChangeRequestExpander;
use Spryker\Zed\ProductBundle\Business\ProductBundle\PersistentCart\ChangeRequestExpanderInterface;
use Spryker\Zed\ProductBundle\Business\ProductBundle\PersistentCart\QuoteItemFinder;
use Spryker\Zed\ProductBundle\Business\ProductBundle\PersistentCart\QuoteItemFinderInterface;
use Spryker\Zed\ProductBundle\Business\ProductBundle\PreCheck\ProductBundleCartActiveCheck;
use Spryker\Zed\ProductBundle\Business\ProductBundle\PreCheck\ProductBundleCartActiveCheckInterface;
use Spryker\Zed\ProductBundle\Business\ProductBundle\Price\PriceReaderInterface;
use Spryker\Zed\ProductBundle\Business\ProductBundle\Price\PriceReaderWithCache;
use Spryker\Zed\ProductBundle\Business\ProductBundle\ProductBundleReader;
use Spryker\Zed\ProductBundle\Business\ProductBundle\ProductBundleWriter;
use Spryker\Zed\ProductBundle\Business\ProductBundle\Quote\QuoteItemsGrouper;
use Spryker\Zed\ProductBundle\Business\ProductBundle\Quote\QuoteItemsGrouperInterface;
use Spryker\Zed\ProductBundle\Business\ProductBundle\Sales\ProductBundleIdHydrator;
use Spryker\Zed\ProductBundle\Business\ProductBundle\Sales\ProductBundleSalesOrderSaver;
use Spryker\Zed\ProductBundle\Business\ProductBundle\Sales\ProductBundlesSalesOrderHydrate;
use Spryker\Zed\ProductBundle\Business\ProductBundle\Status\ProductBundleStatusUpdater;
use Spryker\Zed\ProductBundle\Business\ProductBundle\Status\ProductBundleStatusUpdaterInterface;
use Spryker\Zed\ProductBundle\Business\ProductBundle\Stock\ProductBundleStockHandler;
use Spryker\Zed\ProductBundle\Business\ProductBundle\Stock\ProductBundleStockHandlerInterface;
use Spryker\Zed\ProductBundle\Business\ProductBundle\Stock\ProductBundleStockWriter;
use Spryker\Zed\ProductBundle\Dependency\Facade\ProductBundleToStockFacadeInterface;
use Spryker\Zed\ProductBundle\ProductBundleDependencyProvider;

/**
 * @method \Spryker\Zed\ProductBundle\ProductBundleConfig getConfig()
 * @method \Spryker\Zed\ProductBundle\Persistence\ProductBundleQueryContainerInterface getQueryContainer()
 * @method \Spryker\Zed\ProductBundle\Persistence\ProductBundleRepositoryInterface getRepository()
 */
class ProductBundleBusinessFactory extends AbstractBusinessFactory
{
    /**
     * @return \Spryker\Zed\ProductBundle\Business\ProductBundle\ProductBundleWriterInterface
     */
    public function createProductBundleWriter()
    {
        return new ProductBundleWriter(
            $this->getQueryContainer(),
            $this->createProductBundleStockWriter(),
        );
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Business\ProductBundle\ProductBundleReaderInterface
     */
    public function createProductBundleReader()
    {
        return new ProductBundleReader(
            $this->getQueryContainer(),
            $this->getAvailabilityFacade(),
            $this->getStoreFacade(),
            $this->getRepository(),
            $this->createProductBundleCache(),
        );
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Business\ProductBundle\Price\PriceReaderInterface
     */
    public function createPriceReader(): PriceReaderInterface
    {
        return new PriceReaderWithCache(
            $this->getPriceFacade(),
        );
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Business\ProductBundle\Cart\ProductBundleCartExpanderInterface
     */
    public function createProductBundleCartExpander()
    {
        return new ProductBundleCartExpander(
            $this->getPriceProductFacade(),
            $this->getProductFacade(),
            $this->getLocaleFacade(),
            $this->createProductBundleReader(),
            $this->createPriceReader(),
        );
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Business\ProductBundle\Cart\BundleItemUnfolderInterface
     */
    public function createBundleItemUnfolder(): BundleItemUnfolderInterface
    {
        return new BundleItemUnfolder(
            $this->getPriceProductFacade(),
            $this->getProductFacade(),
            $this->getLocaleFacade(),
            $this->createProductBundleReader(),
            $this->createPriceReader(),
        );
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Business\ProductBundle\Cart\ProductBundleCartExpanderInterface
     */
    public function createProductBundleImageCartExpander()
    {
        return new ProductBundleImageCartExpander($this->getProductImageFacade(), $this->getLocaleFacade());
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Business\ProductBundle\Cart\ProductBundleCartItemGroupKeyExpander
     */
    public function createProductBundleCartItemGroupKeyExpander()
    {
        return new ProductBundleCartItemGroupKeyExpander();
    }

    /**
     * @deprecated Use createProductBundleOrderSaver instead.
     *
     * @return \Spryker\Zed\ProductBundle\Business\ProductBundle\Sales\ProductBundleSalesOrderSaverInterface
     */
    public function createProductBundleSalesOrderSaver()
    {
        return new ProductBundleSalesOrderSaver($this->getSalesQueryContainer());
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Business\ProductBundle\Checkout\ProductBundleOrderSaverInterface
     */
    public function createProductBundleOrderSaver()
    {
        return new ProductBundleOrderSaver($this->getSalesQueryContainer());
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Business\ProductBundle\Calculation\ProductBundlePriceCalculationInterface
     */
    public function createProductBundlePriceCalculator()
    {
        return new ProductBundlePriceCalculation();
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Business\ProductBundle\Cart\ProductBundleCartPostSaveUpdateInterface
     */
    public function createProductBundlePostSaveUpdate()
    {
        return new ProductBundleCartPostSaveUpdate();
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Business\ProductBundle\Cart\BundleRefresherInterface
     */
    public function createBundleItemRefresher(): BundleRefresherInterface
    {
        return new BundleItemRefresher();
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Business\ProductBundle\Availability\PreCheck\ProductBundleCartAvailabilityCheckInterface
     */
    public function createProductBundleCartPreCheck()
    {
        return new ProductBundleCartAvailabilityCheck(
            $this->getAvailabilityFacade(),
            $this->getQueryContainer(),
            $this->getStoreFacade(),
            $this->getConfig(),
            $this->createProductBundleReader(),
        );
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Business\ProductBundle\PreCheck\ProductBundleCartActiveCheckInterface
     */
    public function createProductBundleCartActiveCheck(): ProductBundleCartActiveCheckInterface
    {
        return new ProductBundleCartActiveCheck(
            $this->createProductBundleReader(),
        );
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Business\ProductBundle\CartPriceCheck\ProductBundleCartPriceCheckerInterface
     */
    public function createProductBundleCartPricePreCheck(): ProductBundleCartPriceCheckerInterface
    {
        return new ProductBundleCartPriceChecker(
            $this->getRepository(),
            $this->getPriceProductFacade(),
        );
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Business\ProductBundle\Availability\PreCheck\ProductBundleCheckoutAvailabilityCheckInterface
     */
    public function createProductBundleCheckoutPreCheck()
    {
        return new ProductBundleCheckoutAvailabilityCheck(
            $this->getAvailabilityFacade(),
            $this->getQueryContainer(),
            $this->getStoreFacade(),
            $this->getConfig(),
        );
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Business\ProductBundle\Availability\ProductBundleAvailabilityHandlerInterface
     */
    public function createProductBundleAvailabilityHandler()
    {
        return new ProductBundleAvailabilityHandler(
            $this->getAvailabilityFacade(),
            $this->getQueryContainer(),
            $this->getStockFacade(),
        );
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Business\ProductBundle\Status\ProductBundleStatusUpdaterInterface
     */
    public function createProductBundleStatusUpdater(): ProductBundleStatusUpdaterInterface
    {
        return new ProductBundleStatusUpdater(
            $this->getProductFacade(),
            $this->getRepository(),
        );
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Business\ProductBundle\Stock\ProductBundleStockHandlerInterface
     */
    public function createProductBundleStockHandler(): ProductBundleStockHandlerInterface
    {
        return new ProductBundleStockHandler(
            $this->getQueryContainer(),
            $this->createProductBundleStockWriter(),
        );
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Business\ProductBundle\Stock\ProductBundleStockWriterInterface
     */
    public function createProductBundleStockWriter()
    {
        return new ProductBundleStockWriter(
            $this->getQueryContainer(),
            $this->getStockQueryContainer(),
            $this->createProductBundleAvailabilityHandler(),
            $this->getStoreFacade(),
        );
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Business\ProductBundle\Sales\ProductBundlesSalesOrderHydrateInterface
     */
    public function createProductBundlesSalesOrderHydrate()
    {
        return new ProductBundlesSalesOrderHydrate(
            $this->getSalesQueryContainer(),
            $this->createProductBundlePriceCalculator(),
        );
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Business\ProductBundle\Expander\ProductOptionExpanderInterface
     */
    public function createProductOptionExpander(): ProductOptionExpanderInterface
    {
        return new ProductOptionExpander();
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Business\ProductBundle\Expander\ProductBundleExpanderInterface
     */
    public function createProductBundleExpander(): ProductBundleExpanderInterface
    {
        return new ProductBundleExpander();
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Business\ProductBundle\Cart\ProductBundlePreReloadUpdaterInterface
     */
    public function createProductBundlePreReloadUpdater()
    {
        return new ProductBundlePreReloadUpdater();
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Business\ProductBundle\Sales\ProductBundleIdHydratorInterface
     */
    public function createProductBundlesIdHydrator()
    {
        return new ProductBundleIdHydrator($this->getProductQueryContainer());
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Business\ProductBundle\PersistentCart\ChangeRequestExpanderInterface
     */
    public function createChangeRequestExpander(): ChangeRequestExpanderInterface
    {
        return new ChangeRequestExpander();
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Business\ProductBundle\PersistentCart\BundleItemReplacerInterface
     */
    public function createBundleItemReplacer(): BundleItemReplacerInterface
    {
        return new BundleItemReplacer();
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Business\ProductBundle\PersistentCart\QuoteItemFinderInterface
     */
    public function createQuoteItemFinder(): QuoteItemFinderInterface
    {
        return new QuoteItemFinder();
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Business\ProductBundle\CartNote\QuoteBundleItemsFinderInterface
     */
    public function createQuoteBundleItemsFinder(): QuoteBundleItemsFinderInterface
    {
        return new QuoteBundleItemsFinder();
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Business\ProductBundle\Cart\ProductBundleCartChangeObserverInterface
     */
    public function createProductBundleCartChangeObserver(): ProductBundleCartChangeObserverInterface
    {
        return new ProductBundleCartChangeObserver($this->getMessengerFacade());
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Business\ProductBundle\Quote\QuoteItemsGrouperInterface
     */
    public function createQuoteItemsGrouper(): QuoteItemsGrouperInterface
    {
        return new QuoteItemsGrouper();
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Business\ProductBundle\Cache\ProductBundleCacheInterface
     */
    public function createProductBundleCache(): ProductBundleCacheInterface
    {
        return new ProductBundleCache();
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Business\ProductBundle\Expander\ProductBundleItemExpanderInterface
     */
    public function createProductBundleItemExpander(): ProductBundleItemExpanderInterface
    {
        return new ProductBundleItemExpander(
            $this->getRepository(),
            $this->createProductBundlePriceCalculator(),
        );
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Dependency\Facade\ProductBundleToProductFacadeInterface
     */
    public function getProductFacade()
    {
        return $this->getProvidedDependency(ProductBundleDependencyProvider::FACADE_PRODUCT);
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Dependency\Facade\ProductBundleToProductImageFacadeInterface
     */
    public function getProductImageFacade()
    {
        return $this->getProvidedDependency(ProductBundleDependencyProvider::FACADE_PRODUCT_IMAGE);
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Dependency\Facade\ProductBundleToPriceProductFacadeInterface
     */
    public function getPriceProductFacade()
    {
        return $this->getProvidedDependency(ProductBundleDependencyProvider::FACADE_PRICE_PRODUCT);
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Dependency\Facade\ProductBundleToLocaleFacadeInterface
     */
    public function getLocaleFacade()
    {
        return $this->getProvidedDependency(ProductBundleDependencyProvider::FACADE_LOCALE);
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Dependency\Facade\ProductBundleToAvailabilityFacadeInterface
     */
    public function getAvailabilityFacade()
    {
        return $this->getProvidedDependency(ProductBundleDependencyProvider::FACADE_AVAILABILITY);
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Dependency\QueryContainer\ProductBundleToSalesQueryContainerInterface
     */
    public function getSalesQueryContainer()
    {
        return $this->getProvidedDependency(ProductBundleDependencyProvider::QUERY_CONTAINER_SALES);
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Dependency\QueryContainer\ProductBundleToStockQueryContainerInterface
     */
    public function getStockQueryContainer()
    {
        return $this->getProvidedDependency(ProductBundleDependencyProvider::QUERY_CONTAINER_STOCK);
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Dependency\QueryContainer\ProductBundleToProductQueryContainerInterface
     */
    public function getProductQueryContainer()
    {
        return $this->getProvidedDependency(ProductBundleDependencyProvider::QUERY_CONTAINER_PRODUCT);
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Dependency\Facade\ProductBundleToPriceFacadeInterface
     */
    public function getPriceFacade()
    {
        return $this->getProvidedDependency(ProductBundleDependencyProvider::FACADE_PRICE);
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Dependency\Facade\ProductBundleToStoreFacadeInterface
     */
    public function getStoreFacade()
    {
        return $this->getProvidedDependency(ProductBundleDependencyProvider::FACADE_STORE);
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Dependency\Facade\ProductBundleToMessengerFacadeInterface
     */
    public function getMessengerFacade()
    {
        return $this->getProvidedDependency(ProductBundleDependencyProvider::FACADE_MESSENGER);
    }

    /**
     * @return \Spryker\Zed\ProductBundle\Dependency\Facade\ProductBundleToStockFacadeInterface
     */
    public function getStockFacade(): ProductBundleToStockFacadeInterface
    {
        return $this->getProvidedDependency(ProductBundleDependencyProvider::FACADE_STOCK);
    }
}
