<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\ProductBundle\Grouper;

use ArrayObject;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\ProductOptionTransfer;
use Generated\Shared\Transfer\QuoteTransfer;

class ProductBundleGrouper implements ProductBundleGrouperInterface
{
    /**
     * @var string
     */
    public const BUNDLE_ITEMS = 'bundleItems';

    /**
     * @var string
     */
    public const BUNDLE_PRODUCT = 'bundleProduct';

    /**
     * @var string
     */
    protected const GROUP_KEY_FORMAT = '%s_%s';

    /**
     * @var string
     */
    protected const EMPTY_BUNDLE_IDENTIFIER = 'EMPTY_BUNDLE_IDENTIFIER';

    /**
     * @var array
     */
    protected $bundleGroupKeys = [];

    /**
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     *
     * @return array<\Generated\Shared\Transfer\ItemTransfer>
     */
    public function getItemsWithBundlesItems(QuoteTransfer $quoteTransfer): array
    {
        $items = $this->getGroupedBundleItems($quoteTransfer->getItems(), $quoteTransfer->getBundleItems());
        $items = array_map(function ($groupedItem) {
            if ($groupedItem instanceof ItemTransfer) {
                return $groupedItem;
            }

            return $groupedItem[static::BUNDLE_PRODUCT];
        }, $items);

        return $items;
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\ItemTransfer> $items
     * @param \ArrayObject<int, \Generated\Shared\Transfer\ItemTransfer> $bundleItems
     *
     * @return array
     */
    public function getGroupedBundleItems(ArrayObject $items, ArrayObject $bundleItems)
    {
        $groupedBundleQuantity = $this->getGroupedBundleQuantity($bundleItems, $items);

        $groupedBundleItems = [];

        $itemTransfersGroupedByRelatedBundleItemIdentifier = $this->getItemTransfersGroupedByRelatedBundleItemIdentifier($items);
        foreach ($bundleItems as $bundleItemTransfer) {
            if (!array_key_exists($bundleItemTransfer->getBundleItemIdentifier(), $itemTransfersGroupedByRelatedBundleItemIdentifier)) {
                continue;
            }

            $bundleGroupKey = $this->getBundleItemGroupKey($bundleItemTransfer, $items);

            $itemTransfers = $itemTransfersGroupedByRelatedBundleItemIdentifier[$bundleItemTransfer->getBundleItemIdentifier()];
            foreach ($itemTransfers as $itemTransfer) {
                $groupedBundleItems = $this->getCurrentBundle(
                    $groupedBundleItems,
                    $bundleItemTransfer,
                    $groupedBundleQuantity,
                    $bundleGroupKey,
                );

                $currentBundleItemTransfer = $this->getBundleProduct($groupedBundleItems, $bundleGroupKey);
                if ($currentBundleItemTransfer->getBundleItemIdentifier() !== $itemTransfer->getRelatedBundleItemIdentifier()) {
                    continue;
                }

                $groupedBundleItems[$bundleGroupKey][static::BUNDLE_ITEMS] = $this->groupBundledItems(
                    $groupedBundleItems,
                    $itemTransfer,
                    $bundleGroupKey,
                );
            }
        }

        $groupedBundleItems = $this->updateGroupedBundleItemsAggregatedSubtotal($groupedBundleItems, $bundleItems);

        return array_merge(
            $itemTransfersGroupedByRelatedBundleItemIdentifier[static::EMPTY_BUNDLE_IDENTIFIER] ?? [],
            $groupedBundleItems,
        );
    }

    /**
     * @param \Generated\Shared\Transfer\ItemTransfer $bundleItemTransfer
     * @param \ArrayObject<int, \Generated\Shared\Transfer\ItemTransfer> $items
     *
     * @return string
     */
    protected function getBundleItemGroupKey(ItemTransfer $bundleItemTransfer, ArrayObject $items)
    {
        if (isset($this->bundleGroupKeys[$bundleItemTransfer->getBundleItemIdentifier()])) {
            return $this->bundleGroupKeys[$bundleItemTransfer->getBundleItemIdentifier()];
        }

        $bundleOptions = $this->getBundleOptions($bundleItemTransfer, $items);
        if (count($bundleOptions) == 0) {
            return $this->buildGroupKey($bundleItemTransfer);
        }

        $bundleOptions = $this->sortOptions($bundleOptions);
        $bundleItemTransfer->setProductOptions(new ArrayObject($bundleOptions));

        $this->bundleGroupKeys[$bundleItemTransfer->getBundleItemIdentifier()] = $this->buildGroupKey($bundleItemTransfer) . '_' . $this->combineOptionParts($bundleOptions);

        return $this->bundleGroupKeys[$bundleItemTransfer->getBundleItemIdentifier()];
    }

    /**
     * @param \Generated\Shared\Transfer\ItemTransfer $itemTransfer
     *
     * @return string
     */
    protected function buildGroupKey(ItemTransfer $itemTransfer): string
    {
        if ($itemTransfer->getGroupKeyPrefix()) {
            return sprintf(static::GROUP_KEY_FORMAT, $itemTransfer->getGroupKeyPrefix(), $itemTransfer->getSku());
        }

        return $itemTransfer->getSku();
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    protected function sortOptions(array $options)
    {
        usort(
            $options,
            function (ProductOptionTransfer $productOptionLeft, ProductOptionTransfer $productOptionRight) {
                return ($productOptionLeft->getSku() < $productOptionRight->getSku()) ? -1 : 1;
            },
        );

        return $options;
    }

    /**
     * @param array<\Generated\Shared\Transfer\ProductOptionTransfer> $sortedProductOptions
     *
     * @return string
     */
    protected function combineOptionParts(array $sortedProductOptions)
    {
        $groupKeyPart = [];
        foreach ($sortedProductOptions as $productOptionTransfer) {
            if (!$productOptionTransfer->getSku()) {
                continue;
            }

            $groupKeyPart[] = $productOptionTransfer->getSku();
        }

        return implode('_', $groupKeyPart);
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\ItemTransfer> $bundleItems
     * @param \ArrayObject<int, \Generated\Shared\Transfer\ItemTransfer> $items
     *
     * @return array<string, int>
     */
    protected function getGroupedBundleQuantity(ArrayObject $bundleItems, ArrayObject $items)
    {
        $groupedBundleQuantity = [];
        foreach ($bundleItems as $bundleItemTransfer) {
            $bundleGroupKey = $this->getBundleItemGroupKey($bundleItemTransfer, $items);
            /** @var int $quantity */
            $quantity = $bundleItemTransfer->getQuantity();
            if (!isset($groupedBundleQuantity[$bundleGroupKey])) {
                $groupedBundleQuantity[$bundleGroupKey] = $quantity;
            } else {
                $groupedBundleQuantity[$bundleGroupKey] += $quantity;
            }
        }

        return $groupedBundleQuantity;
    }

    /**
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $bundleItems
     * @param \Generated\Shared\Transfer\ItemTransfer $bundleItemTransfer
     * @param array $groupedBundleQuantity
     * @param string $bundleGroupKey
     *
     * @return array
     */
    protected function getCurrentBundle(
        array $bundleItems,
        ItemTransfer $bundleItemTransfer,
        array $groupedBundleQuantity,
        $bundleGroupKey
    ) {
        if (isset($bundleItems[$bundleGroupKey])) {
            return $bundleItems;
        }

        $bundleProduct = clone $bundleItemTransfer;

        $bundleProduct->setSumSubtotalAggregation(0);
        $bundleProduct->setUnitSubtotalAggregation(0);
        $bundleProduct->setQuantity($groupedBundleQuantity[$bundleGroupKey]);
        $bundleProduct->setGroupKey($bundleGroupKey);

        $bundleItems[$bundleGroupKey] = [
            static::BUNDLE_PRODUCT => $bundleProduct,
            static::BUNDLE_ITEMS => [],
        ];

        return $bundleItems;
    }

    /**
     * @param array<string, array<string, array<string, \Generated\Shared\Transfer\ItemTransfer>>> $bundleItems
     * @param \Generated\Shared\Transfer\ItemTransfer $bundledItemTransfer
     * @param string $bundleGroupKey
     *
     * @return array<string, \Generated\Shared\Transfer\ItemTransfer>
     */
    protected function groupBundledItems(array $bundleItems, ItemTransfer $bundledItemTransfer, $bundleGroupKey)
    {
        $currentBundledItems = $this->getAlreadyBundledItems($bundleItems, $bundleGroupKey);
        $currentBundleIdentifer = $bundledItemTransfer->getSku() . $bundledItemTransfer->getRelatedBundleItemIdentifier();

        if (!isset($currentBundledItems[$currentBundleIdentifer])) {
            $currentBundledItems[$currentBundleIdentifer] = clone $bundledItemTransfer;
        } else {
            $currentBundleItemTransfer = $currentBundledItems[$currentBundleIdentifer];
            $currentBundleItemTransfer->setQuantity(
                $currentBundleItemTransfer->getQuantity() + $bundledItemTransfer->getQuantity(),
            );
        }

        return $currentBundledItems;
    }

    /**
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $bundleItems
     * @param string $bundleGroupKey
     *
     * @return \Generated\Shared\Transfer\ItemTransfer
     */
    protected function getBundleProduct(array $bundleItems, $bundleGroupKey)
    {
        return $bundleItems[$bundleGroupKey][static::BUNDLE_PRODUCT];
    }

    /**
     * @param array<string, array<string, array<string, \Generated\Shared\Transfer\ItemTransfer>>> $bundleItems
     * @param string $bundleGroupKey
     *
     * @return array<string, \Generated\Shared\Transfer\ItemTransfer>
     */
    protected function getAlreadyBundledItems(array $bundleItems, $bundleGroupKey)
    {
        return $bundleItems[$bundleGroupKey][static::BUNDLE_ITEMS];
    }

    /**
     * @param \Generated\Shared\Transfer\ItemTransfer $itemTransfer
     * @param \ArrayObject<int, \Generated\Shared\Transfer\ItemTransfer> $items
     *
     * @return array<\Generated\Shared\Transfer\ProductOptionTransfer>
     */
    protected function getBundleOptions(ItemTransfer $itemTransfer, ArrayObject $items)
    {
        foreach ($items as $cartItemTransfer) {
            if (
                $itemTransfer->getBundleItemIdentifier() === $cartItemTransfer->getRelatedBundleItemIdentifier()
                && count($cartItemTransfer->getProductOptions()) > 0
            ) {
                return (array)$cartItemTransfer->getProductOptions();
            }
        }

        return [];
    }

    /**
     * @param array $groupedBundleItems
     * @param \ArrayObject<int, \Generated\Shared\Transfer\ItemTransfer> $bundleItems
     *
     * @return array<\Generated\Shared\Transfer\ItemTransfer>
     */
    protected function updateGroupedBundleItemsAggregatedSubtotal(array $groupedBundleItems, ArrayObject $bundleItems)
    {
        $bundleItemTransfersGroupedByGroupKey = $this->getBundleItemTransfersGroupedByGroupKey($bundleItems);
        foreach ($groupedBundleItems as $groupedBundle) {
            /** @var \Generated\Shared\Transfer\ItemTransfer $groupedBundleItemTransfer */
            $groupedBundleItemTransfer = $groupedBundle[static::BUNDLE_PRODUCT];

            $relatedBundleItems = $bundleItemTransfersGroupedByGroupKey[$groupedBundleItemTransfer->getGroupKey()] ?? [];
            foreach ($relatedBundleItems as $bundleItemTransfer) {
                $groupedBundleItemTransfer->setUnitSubtotalAggregation(
                    $groupedBundleItemTransfer->getUnitSubtotalAggregation() + $bundleItemTransfer->getUnitSubtotalAggregation(),
                );

                $groupedBundleItemTransfer->setSumSubtotalAggregation(
                    $groupedBundleItemTransfer->getSumSubtotalAggregation() + $bundleItemTransfer->getSumSubtotalAggregation(),
                );
            }
        }

        return $groupedBundleItems;
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     *
     * @return array<string, list<\Generated\Shared\Transfer\ItemTransfer>>
     */
    protected function getItemTransfersGroupedByRelatedBundleItemIdentifier(ArrayObject $itemTransfers): array
    {
        $itemTransfersGroupedByRelatedBundleItemIdentifier = [];
        foreach ($itemTransfers as $itemTransfer) {
            if ($itemTransfer->getRelatedBundleItemIdentifier() === null) {
                $itemTransfersGroupedByRelatedBundleItemIdentifier[static::EMPTY_BUNDLE_IDENTIFIER][] = $itemTransfer;

                continue;
            }

            $itemTransfersGroupedByRelatedBundleItemIdentifier[$itemTransfer->getRelatedBundleItemIdentifierOrFail()][] = $itemTransfer;
        }

        return $itemTransfersGroupedByRelatedBundleItemIdentifier;
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\ItemTransfer> $bundleItemTransfers
     *
     * @return array<string, list<\Generated\Shared\Transfer\ItemTransfer>>
     */
    protected function getBundleItemTransfersGroupedByGroupKey(ArrayObject $bundleItemTransfers): array
    {
        $bundleItemTransfersGroupedByGroupKey = [];
        foreach ($bundleItemTransfers as $bundleItemTransfer) {
            $bundleItemTransfersGroupedByGroupKey[$bundleItemTransfer->getGroupKey()][] = $bundleItemTransfer;
        }

        return $bundleItemTransfersGroupedByGroupKey;
    }
}
