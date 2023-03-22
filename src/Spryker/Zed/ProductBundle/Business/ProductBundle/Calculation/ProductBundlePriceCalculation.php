<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ProductBundle\Business\ProductBundle\Calculation;

use Generated\Shared\Transfer\CalculableObjectTransfer;
use Generated\Shared\Transfer\ItemMetadataTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\OrderTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Orm\Zed\Sales\Persistence\Base\SpySalesOrderItem;

class ProductBundlePriceCalculation implements ProductBundlePriceCalculationInterface
{
    /**
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     *
     * @return \Generated\Shared\Transfer\QuoteTransfer
     */
    public function calculate(QuoteTransfer $quoteTransfer)
    {
        $this->resetBundlePriceAmounts($quoteTransfer);

        foreach ($quoteTransfer->getBundleItems() as $bundleItemTransfer) {
            foreach ($quoteTransfer->getItems() as $itemTransfer) {
                if ($bundleItemTransfer->getBundleItemIdentifier() !== $itemTransfer->getRelatedBundleItemIdentifier()) {
                    continue;
                }
                $this->calculateBundleAmounts($bundleItemTransfer, $itemTransfer);
            }
        }

        return $quoteTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\CalculableObjectTransfer $calculableObjectTransfer
     *
     * @return \Generated\Shared\Transfer\CalculableObjectTransfer
     */
    public function calculateForCalculableObjectTransfer(CalculableObjectTransfer $calculableObjectTransfer): CalculableObjectTransfer
    {
        $this->resetBundlePriceAmountsCalculableObjectTransfer($calculableObjectTransfer);

        $itemTransfersGroupedByRelatedBundleItemIdentifier = $this->getItemTransfersGroupedByRelatedBundleItemIdentifier($calculableObjectTransfer);
        foreach ($calculableObjectTransfer->getBundleItems() as $bundleItemTransfer) {
            $itemTransfers = $itemTransfersGroupedByRelatedBundleItemIdentifier[$bundleItemTransfer->getBundleItemIdentifier()] ?? [];
            foreach ($itemTransfers as $itemTransfer) {
                $this->calculateBundleAmounts($bundleItemTransfer, $itemTransfer);
            }
        }

        return $calculableObjectTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\ItemTransfer $bundleItemTransfer
     * @param \Generated\Shared\Transfer\ItemTransfer $itemTransfer
     *
     * @return void
     */
    public function calculateBundleAmounts(ItemTransfer $bundleItemTransfer, ItemTransfer $itemTransfer): void
    {
        $this->addPrice($bundleItemTransfer, $itemTransfer);
        $this->addNetPrice($bundleItemTransfer, $itemTransfer);
        $this->addGrossPrice($bundleItemTransfer, $itemTransfer);
        $this->addItemSubtotalAggregation($bundleItemTransfer, $itemTransfer);
        $this->addDiscounts($bundleItemTransfer, $itemTransfer);
        $this->addItemPriceToPayAggregation($bundleItemTransfer, $itemTransfer);
    }

    /**
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrderItem $orderItemEntity
     *
     * @return \Generated\Shared\Transfer\ItemTransfer
     */
    protected function mapBundledItemTransferFromSalesOrderItemEntity(SpySalesOrderItem $orderItemEntity)
    {
        $bundleItemTransfer = new ItemTransfer();
        $bundleItemTransfer->setBundleItemIdentifier((string)$orderItemEntity->getFkSalesOrderItemBundle());
        $bundleItemTransfer->setQuantity($orderItemEntity->getQuantity());
        $salesOrderItemBundle = $orderItemEntity->getSalesOrderItemBundle();
        $bundleItemTransfer->fromArray($salesOrderItemBundle->toArray(), true);

        $productMetadataTransfer = new ItemMetadataTransfer();
        $productMetadataTransfer->setImage($salesOrderItemBundle->getImage());

        $bundleItemTransfer->setMetadata($productMetadataTransfer);

        return $bundleItemTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\ItemTransfer $bundleItemTransfer
     * @param \Generated\Shared\Transfer\ItemTransfer $itemTransfer
     *
     * @return void
     */
    protected function addDiscounts(ItemTransfer $bundleItemTransfer, ItemTransfer $itemTransfer)
    {
        $bundleItemTransfer->setUnitDiscountAmountFullAggregation(
            $bundleItemTransfer->getUnitDiscountAmountFullAggregation() + $itemTransfer->getUnitDiscountAmountFullAggregation(),
        );

        $bundleItemTransfer->setSumDiscountAmountFullAggregation(
            $bundleItemTransfer->getSumDiscountAmountFullAggregation() + $itemTransfer->getSumDiscountAmountFullAggregation(),
        );

        $bundleItemTransfer->setUnitDiscountAmountAggregation(
            $bundleItemTransfer->getUnitDiscountAmountAggregation() + $itemTransfer->getUnitDiscountAmountAggregation(),
        );

        $bundleItemTransfer->setSumDiscountAmountAggregation(
            $bundleItemTransfer->getSumDiscountAmountAggregation() + $itemTransfer->getSumDiscountAmountAggregation(),
        );
    }

    /**
     * @param \Generated\Shared\Transfer\ItemTransfer $bundleItemTransfer
     * @param \Generated\Shared\Transfer\ItemTransfer $itemTransfer
     *
     * @return void
     */
    protected function addGrossPrice(ItemTransfer $bundleItemTransfer, ItemTransfer $itemTransfer)
    {
        $bundleItemTransfer->setUnitGrossPrice(
            $bundleItemTransfer->getUnitGrossPrice() + $itemTransfer->getUnitGrossPrice(),
        );

        $bundleItemTransfer->setSumGrossPrice(
            $bundleItemTransfer->getSumGrossPrice() + $itemTransfer->getSumGrossPrice(),
        );
    }

    /**
     * @param \Generated\Shared\Transfer\ItemTransfer $bundleItemTransfer
     * @param \Generated\Shared\Transfer\ItemTransfer $itemTransfer
     *
     * @return void
     */
    protected function addPrice(ItemTransfer $bundleItemTransfer, ItemTransfer $itemTransfer)
    {
        $bundleItemTransfer->setUnitPrice(
            $bundleItemTransfer->getUnitPrice() + $itemTransfer->getUnitPrice(),
        );

        $bundleItemTransfer->setSumPrice(
            $bundleItemTransfer->getSumPrice() + $itemTransfer->getSumPrice(),
        );
    }

    /**
     * @param \Generated\Shared\Transfer\ItemTransfer $bundleItemTransfer
     * @param \Generated\Shared\Transfer\ItemTransfer $itemTransfer
     *
     * @return void
     */
    protected function addNetPrice(ItemTransfer $bundleItemTransfer, ItemTransfer $itemTransfer)
    {
        $bundleItemTransfer->setUnitNetPrice(
            $bundleItemTransfer->getUnitNetPrice() + $itemTransfer->getUnitNetPrice(),
        );

        $bundleItemTransfer->setSumNetPrice(
            $bundleItemTransfer->getSumNetPrice() + $itemTransfer->getSumNetPrice(),
        );
    }

    /**
     * @param \Generated\Shared\Transfer\ItemTransfer $bundleItemTransfer
     * @param \Generated\Shared\Transfer\ItemTransfer $itemTransfer
     *
     * @return void
     */
    protected function addItemSubtotalAggregation(ItemTransfer $bundleItemTransfer, ItemTransfer $itemTransfer)
    {
        $bundleItemTransfer->setUnitSubtotalAggregation(
            $bundleItemTransfer->getUnitSubtotalAggregation() + $itemTransfer->getUnitSubtotalAggregation(),
        );

        $bundleItemTransfer->setSumSubtotalAggregation(
            $bundleItemTransfer->getSumSubtotalAggregation() + $itemTransfer->getSumSubtotalAggregation(),
        );
    }

    /**
     * @param \Generated\Shared\Transfer\ItemTransfer $bundleItemTransfer
     * @param \Generated\Shared\Transfer\ItemTransfer $itemTransfer
     *
     * @return void
     */
    protected function addItemPriceToPayAggregation(ItemTransfer $bundleItemTransfer, ItemTransfer $itemTransfer)
    {
        $bundleItemTransfer->setUnitPriceToPayAggregation(
            $bundleItemTransfer->getUnitPriceToPayAggregation() + $itemTransfer->getUnitPriceToPayAggregation(),
        );

        $bundleItemTransfer->setSumPriceToPayAggregation(
            $bundleItemTransfer->getSumPriceToPayAggregation() + $itemTransfer->getSumPriceToPayAggregation(),
        );
    }

    /**
     * @param \Generated\Shared\Transfer\OrderTransfer $orderTransfer
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrderItem $salesOrderItemEntity
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $bundledProducts
     *
     * @return array
     */
    public function calculateForBundleItems(
        OrderTransfer $orderTransfer,
        SpySalesOrderItem $salesOrderItemEntity,
        array $bundledProducts
    ) {
        foreach ($orderTransfer->getItems() as $itemTransfer) {
            if ($itemTransfer->getIdSalesOrderItem() !== $salesOrderItemEntity->getIdSalesOrderItem()) {
                continue;
            }

            if (!isset($bundledProducts[$salesOrderItemEntity->getFkSalesOrderItemBundle()])) {
                $bundleItemTransfer = $this->mapBundledItemTransferFromSalesOrderItemEntity($salesOrderItemEntity);
                $bundledProducts[$salesOrderItemEntity->getFkSalesOrderItemBundle()] = $bundleItemTransfer;
            }

            $bundleItemTransfer = $bundledProducts[$salesOrderItemEntity->getFkSalesOrderItemBundle()];

            $itemTransfer->setRelatedBundleItemIdentifier((string)$salesOrderItemEntity->getFkSalesOrderItemBundle());

            $this->calculateBundleAmounts($bundleItemTransfer, $itemTransfer);
        }

        return $bundledProducts;
    }

    /**
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     *
     * @return void
     */
    protected function resetBundlePriceAmounts(QuoteTransfer $quoteTransfer)
    {
        foreach ($quoteTransfer->getBundleItems() as $bundleItemTransfer) {
            $this->resetBundleItemPrice($bundleItemTransfer);
        }
    }

    /**
     * @param \Generated\Shared\Transfer\CalculableObjectTransfer $calculableObjectTransfer
     *
     * @return \Generated\Shared\Transfer\CalculableObjectTransfer
     */
    protected function resetBundlePriceAmountsCalculableObjectTransfer(CalculableObjectTransfer $calculableObjectTransfer): CalculableObjectTransfer
    {
        foreach ($calculableObjectTransfer->getBundleItems() as $bundleItemTransfer) {
            $this->resetBundleItemPrice($bundleItemTransfer);
        }

        return $calculableObjectTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\ItemTransfer $bundleItemTransfer
     *
     * @return \Generated\Shared\Transfer\ItemTransfer
     */
    protected function resetBundleItemPrice(ItemTransfer $bundleItemTransfer): ItemTransfer
    {
        $bundleItemTransfer->setUnitGrossPrice(0);
        $bundleItemTransfer->setSumGrossPrice(0);
        $bundleItemTransfer->setUnitPrice(0);
        $bundleItemTransfer->setSumPrice(0);
        $bundleItemTransfer->setUnitNetPrice(0);
        $bundleItemTransfer->setSumNetPrice(0);
        $bundleItemTransfer->setUnitSubtotalAggregation(0);
        $bundleItemTransfer->setSumSubtotalAggregation(0);
        $bundleItemTransfer->setUnitDiscountAmountAggregation(0);
        $bundleItemTransfer->setSumDiscountAmountAggregation(0);
        $bundleItemTransfer->setUnitDiscountAmountFullAggregation(0);
        $bundleItemTransfer->setSumDiscountAmountFullAggregation(0);
        $bundleItemTransfer->setUnitPriceToPayAggregation(0);
        $bundleItemTransfer->setSumPriceToPayAggregation(0);

        return $bundleItemTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\CalculableObjectTransfer $calculableObjectTransfer
     *
     * @return array<string, list<\Generated\Shared\Transfer\ItemTransfer>>
     */
    protected function getItemTransfersGroupedByRelatedBundleItemIdentifier(CalculableObjectTransfer $calculableObjectTransfer): array
    {
        $itemTransfersGroupedByRelatedBundleItemIdentifier = [];
        foreach ($calculableObjectTransfer->getItems() as $itemTransfer) {
            if ($itemTransfer->getRelatedBundleItemIdentifier() === null) {
                continue;
            }

            $itemTransfersGroupedByRelatedBundleItemIdentifier[$itemTransfer->getRelatedBundleItemIdentifierOrFail()][] = $itemTransfer;
        }

        return $itemTransfersGroupedByRelatedBundleItemIdentifier;
    }
}
