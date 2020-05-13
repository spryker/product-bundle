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
use Orm\Zed\ProductBundle\Persistence\Base\SpySalesOrderItemBundle;
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
     * @param \Orm\Zed\ProductBundle\Persistence\Base\SpySalesOrderItemBundle[]|\Propel\Runtime\Collection\ObjectCollection $salesOrderItemBundleEntities
     *
     * @return \Generated\Shared\Transfer\ItemTransfer[]
     */
    public function mapSalesOrderItemEntitiesToBundleItemTransfers(
        ObjectCollection $salesOrderItemBundleEntities
    ): array {
        $bundleItemTransfers = [];

        foreach ($salesOrderItemBundleEntities as $salesOrderItemBundleEntity) {
            $bundleItemTransfer = $this->mapSalesOrderItemEntityToBundleItemTransfer($salesOrderItemBundleEntity);

            foreach ($salesOrderItemBundleEntity->getSalesOrderItems() as $salesOrderItemEntity) {
                $bundleItemTransfer->setQuantity($salesOrderItemEntity->getQuantity());
                $bundleItemTransfers[$salesOrderItemEntity->getIdSalesOrderItem()] = $bundleItemTransfer;
            }
        }

        return $bundleItemTransfers;
    }

    /**
     * @param \Orm\Zed\ProductBundle\Persistence\Base\SpySalesOrderItemBundle $salesOrderItemBundleEntity
     *
     * @return \Generated\Shared\Transfer\ItemTransfer
     */
    protected function mapSalesOrderItemEntityToBundleItemTransfer(SpySalesOrderItemBundle $salesOrderItemBundleEntity): ItemTransfer {
        $productMetadataTransfer = (new ItemMetadataTransfer())
            ->setImage($salesOrderItemBundleEntity->getImage());

        return (new ItemTransfer())
            ->setBundleItemIdentifier((string)$salesOrderItemBundleEntity->getIdSalesOrderItemBundle())
            ->setMetadata($productMetadataTransfer)
            ->fromArray($salesOrderItemBundleEntity->toArray(), true);
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
