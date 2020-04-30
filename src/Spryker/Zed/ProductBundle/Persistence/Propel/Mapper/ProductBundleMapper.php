<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ProductBundle\Persistence\Propel\Mapper;

use ArrayObject;
use Generated\Shared\Transfer\ItemMetadataTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\ProductBundleCollectionTransfer;
use Generated\Shared\Transfer\ProductBundleTransfer;
use Generated\Shared\Transfer\ProductForBundleTransfer;
use Orm\Zed\Sales\Persistence\Base\SpySalesOrderItem;
use Propel\Runtime\Collection\ObjectCollection;

class ProductBundleMapper
{
    /**
     * @param \Orm\Zed\ProductBundle\Persistence\Base\SpyProductBundle[] $productBundleEntities
     *
     * @return \Generated\Shared\Transfer\ProductForBundleTransfer[]
     */
    public function mapProductBundleEntitiesToProductForBundleTransfers(
        array $productBundleEntities
    ): array {
        $productForBundleTransfers = [];
        foreach ($productBundleEntities as $productBundleEntity) {
            $productForBundleTransfers[] = (new ProductForBundleTransfer())->fromArray(
                $productBundleEntity->getSpyProductRelatedByFkBundledProduct()->toArray(),
                true
            )
                ->setIdProductConcrete($productBundleEntity->getFkBundledProduct())
                ->setIdProductBundle($productBundleEntity->getFkProduct())
                ->setQuantity($productBundleEntity->getQuantity())
                ->setBundleSku($productBundleEntity->getSpyProductRelatedByFkProduct()->getSku());
        }

        return $productForBundleTransfers;
    }

    /**
     * @param \Orm\Zed\ProductBundle\Persistence\Base\SpyProductBundle[] $productBundleEntities
     * @param \Generated\Shared\Transfer\ProductBundleCollectionTransfer $productBundleCollectionTransfer
     *
     * @return \Generated\Shared\Transfer\ProductBundleCollectionTransfer
     */
    public function mapProductBundleEntitiesToProductBundleCollectionTransfer(
        array $productBundleEntities,
        ProductBundleCollectionTransfer $productBundleCollectionTransfer
    ): ProductBundleCollectionTransfer {
        $productForBundleTransfers = $this->mapProductBundleEntitiesToProductBundleTransfers($productBundleEntities);
        $productBundleCollectionTransfer->setProductBundles(new ArrayObject($productForBundleTransfers));

        return $productBundleCollectionTransfer;
    }

    /**
     * @param \Orm\Zed\Sales\Persistence\Base\SpySalesOrderItem[]|\Propel\Runtime\Collection\ObjectCollection $salesOrderItemEntities
     *
     * @return \Generated\Shared\Transfer\ItemTransfer[]
     */
    public function mapSalesOrderItemEntitiesToBundleItemTransfers(ObjectCollection $salesOrderItemEntities): array
    {
        $uniqueBundleItemTransfers = [];
        $bundleItemTransfers = [];

        foreach ($salesOrderItemEntities as $salesOrderItemEntity) {
            $bundleItemIdentifier = $salesOrderItemEntity->getFkSalesOrderItemBundle();
            if (!isset($uniqueBundleItemTransfers[$bundleItemIdentifier])) {
                $uniqueBundleItemTransfers[$bundleItemIdentifier] = $this->mapSalesOrderItemEntityToBundleItemTransfer($salesOrderItemEntity);
            }

            $bundleItemTransfers[$salesOrderItemEntity->getIdSalesOrderItem()] = $uniqueBundleItemTransfers[$bundleItemIdentifier];
        }

        return $bundleItemTransfers;
    }

    /**
     * @param \Orm\Zed\Sales\Persistence\Base\SpySalesOrderItem $spySalesOrderItem
     *
     * @return \Generated\Shared\Transfer\ItemTransfer
     */
    protected function mapSalesOrderItemEntityToBundleItemTransfer(SpySalesOrderItem $spySalesOrderItem): ItemTransfer
    {
        $salesOrderItemBundle = $spySalesOrderItem->getSalesOrderItemBundle();
        $productMetadataTransfer = (new ItemMetadataTransfer())
            ->setImage($salesOrderItemBundle->getImage());

        return (new ItemTransfer())
            ->setBundleItemIdentifier((string)$spySalesOrderItem->getFkSalesOrderItemBundle())
            ->setQuantity($spySalesOrderItem->getQuantity())
            ->setMetadata($productMetadataTransfer)
            ->fromArray($salesOrderItemBundle->toArray(), true);
    }

    /**
     * @param \Orm\Zed\ProductBundle\Persistence\Base\SpyProductBundle[] $productBundleEntities
     *
     * @return \Generated\Shared\Transfer\ProductBundleTransfer[]
     */
    protected function mapProductBundleEntitiesToProductBundleTransfers(
        array $productBundleEntities
    ): array {
        $productForBundleTransfers = [];
        foreach ($productBundleEntities as $productBundleEntity) {
            $productForBundleTransfers[] = (new ProductBundleTransfer())
                ->setBundledProducts(new ArrayObject($this->mapProductBundleEntitiesToProductForBundleTransfers($productBundleEntities)))
                ->setIdProductConcreteBundle($productBundleEntity->getFkProduct());
        }

        return $productForBundleTransfers;
    }
}
