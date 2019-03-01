<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ProductBundle\Persistence;

use Generated\Shared\Transfer\ProductBundleCollectionTransfer;
use Spryker\Zed\Kernel\Persistence\AbstractRepository;

/**
 * @method \Spryker\Zed\ProductBundle\Persistence\ProductBundlePersistenceFactory getFactory()
 */
class ProductBundleRepository extends AbstractRepository implements ProductBundleRepositoryInterface
{
    /**
     * @param int $idProductConcrete
     *
     * @return \Generated\Shared\Transfer\ProductBundleCollectionTransfer
     */
    public function getProductBundleCollectionByAssignedIdProductConcrete(int $idProductConcrete): ProductBundleCollectionTransfer
    {
        $productBundleEntities = $this->getFactory()
            ->createProductBundleQuery()
            ->filterByFkBundledProduct($idProductConcrete)
            ->joinWithSpyProductRelatedByFkBundledProduct()
            ->find();

        return $this->getFactory()
            ->createProductBundleMapper()
            ->mapProductBundleEntitiesToProductBundleCollectionTransfer($productBundleEntities, new ProductBundleCollectionTransfer());
    }
}
