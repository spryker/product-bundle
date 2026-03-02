<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ProductBundle\Business\ProductBundle\Quote;

use ArrayObject;
use Generated\Shared\Transfer\ItemCollectionTransfer;
use Generated\Shared\Transfer\QuoteTransfer;

class QuoteItemsGrouper implements QuoteItemsGrouperInterface
{
    public function extractQuoteItems(QuoteTransfer $quoteTransfer): ItemCollectionTransfer
    {
        $itemCollectionTransfer = new ItemCollectionTransfer();
        $itemCollectionTransfer->setItems($quoteTransfer->getItems());

        $itemCollectionTransfer = $this->filterBundledItems($itemCollectionTransfer);
        $itemCollectionTransfer = $this->addBundleItems($itemCollectionTransfer, $quoteTransfer);

        return $itemCollectionTransfer;
    }

    protected function filterBundledItems(ItemCollectionTransfer $itemCollectionTransfer): ItemCollectionTransfer
    {
        $items = new ArrayObject();
        foreach ($itemCollectionTransfer->getItems() as $itemTransfer) {
            if ($itemTransfer->getRelatedBundleItemIdentifier()) {
                continue;
            }
            $items->append($itemTransfer);
        }
        $itemCollectionTransfer->setItems($items);

        return $itemCollectionTransfer;
    }

    protected function addBundleItems(ItemCollectionTransfer $itemCollectionTransfer, QuoteTransfer $quoteTransfer): ItemCollectionTransfer
    {
        $itemsProcessedIndex = [];
        foreach ($quoteTransfer->getBundleItems() as $itemTransfer) {
            if (!isset($itemsProcessedIndex[$itemTransfer->getGroupKey()])) {
                $itemsProcessedIndex[$itemTransfer->getGroupKey()] = true;
                $itemTransfer->setQuantity($this->getBundledProductTotalQuantity($quoteTransfer, $itemTransfer->getGroupKey()));
                $itemCollectionTransfer->addItem($itemTransfer);
            }
        }

        return $itemCollectionTransfer;
    }

    protected function getBundledProductTotalQuantity(QuoteTransfer $quoteTransfer, string $groupKey): int
    {
        $bundleItemQuantity = 0;
        foreach ($quoteTransfer->getBundleItems() as $bundleItemTransfer) {
            if ($bundleItemTransfer->getGroupKey() !== $groupKey) {
                continue;
            }
            $bundleItemQuantity += $bundleItemTransfer->getQuantity();
        }

        return $bundleItemQuantity;
    }
}
