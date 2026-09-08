<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\ProductBundle\Business\Plugin\Product;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ProductBundleTransfer;
use Generated\Shared\Transfer\ProductConcreteCollectionRequestTransfer;
use Generated\Shared\Transfer\ProductConcreteCollectionResponseTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;
use Generated\Shared\Transfer\ProductForBundleTransfer;
use Spryker\Zed\ProductBundle\Communication\Plugin\Product\ProductBundleProductConcreteCollectionCreateValidatorPlugin;
use SprykerTest\Zed\ProductBundle\ProductBundleBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group ProductBundle
 * @group Business
 * @group Plugin
 * @group Product
 * @group ProductBundleProductConcreteCollectionCreateValidatorPluginTest
 *
 * Add your own group annotations below this line
 */
class ProductBundleProductConcreteCollectionCreateValidatorPluginTest extends Unit
{
    protected const string UNKNOWN_BUNDLED_SKU = 'non-existent-bundled-sku';

    protected const string BUNDLE_SKU = 'bundle-concrete-sku';

    protected ProductBundleBusinessTester $tester;

    public function testValidateReturnsErrorWhenBundledSkuDoesNotExist(): void
    {
        // Arrange
        $productConcreteCollectionRequestTransfer = $this->createRequest(static::BUNDLE_SKU, static::UNKNOWN_BUNDLED_SKU);

        // Act
        $productConcreteCollectionResponseTransfer = (new ProductBundleProductConcreteCollectionCreateValidatorPlugin())->validate(
            $productConcreteCollectionRequestTransfer,
            new ProductConcreteCollectionResponseTransfer(),
        );

        // Assert
        $this->assertCount(1, $productConcreteCollectionResponseTransfer->getErrors());
        $this->assertSame(static::BUNDLE_SKU, $productConcreteCollectionResponseTransfer->getErrors()->offsetGet(0)->getEntityIdentifier());
    }

    public function testValidateReturnsErrorWhenBundleContainsItself(): void
    {
        // Arrange
        $productConcreteCollectionRequestTransfer = $this->createRequest(static::BUNDLE_SKU, static::BUNDLE_SKU);

        // Act
        $productConcreteCollectionResponseTransfer = (new ProductBundleProductConcreteCollectionCreateValidatorPlugin())->validate(
            $productConcreteCollectionRequestTransfer,
            new ProductConcreteCollectionResponseTransfer(),
        );

        // Assert
        $this->assertCount(1, $productConcreteCollectionResponseTransfer->getErrors());
        $this->assertStringContainsString('must not contain itself', (string)$productConcreteCollectionResponseTransfer->getErrors()->offsetGet(0)->getMessage());
    }

    public function testValidatePassesWhenBundledSkuExists(): void
    {
        // Arrange
        $bundledProductConcreteTransfer = $this->tester->haveProduct();
        $productConcreteCollectionRequestTransfer = $this->createRequest(
            static::BUNDLE_SKU,
            (string)$bundledProductConcreteTransfer->getSku(),
        );

        // Act
        $productConcreteCollectionResponseTransfer = (new ProductBundleProductConcreteCollectionCreateValidatorPlugin())->validate(
            $productConcreteCollectionRequestTransfer,
            new ProductConcreteCollectionResponseTransfer(),
        );

        // Assert
        $this->assertCount(0, $productConcreteCollectionResponseTransfer->getErrors());
    }

    public function testValidatePassesWhenNoProductBundleProvided(): void
    {
        // Arrange
        $productConcreteCollectionRequestTransfer = (new ProductConcreteCollectionRequestTransfer())
            ->addProduct((new ProductConcreteTransfer())->setSku(static::BUNDLE_SKU));

        // Act
        $productConcreteCollectionResponseTransfer = (new ProductBundleProductConcreteCollectionCreateValidatorPlugin())->validate(
            $productConcreteCollectionRequestTransfer,
            new ProductConcreteCollectionResponseTransfer(),
        );

        // Assert
        $this->assertCount(0, $productConcreteCollectionResponseTransfer->getErrors());
    }

    protected function createRequest(string $bundleSku, string $bundledSku): ProductConcreteCollectionRequestTransfer
    {
        return (new ProductConcreteCollectionRequestTransfer())
            ->addProduct(
                (new ProductConcreteTransfer())
                    ->setSku($bundleSku)
                    ->setProductBundle(
                        (new ProductBundleTransfer())->addBundledProduct(
                            (new ProductForBundleTransfer())->setSku($bundledSku),
                        ),
                    ),
            );
    }
}
