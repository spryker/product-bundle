<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ProductBundle\Dependency\Facade;

interface ProductBundleToStoreFacadeInterface
{
    /**
     * @return \Generated\Shared\Transfer\StoreTransfer
     */
    public function getCurrentStore();

    /**
     * @return bool
     */
    public function isCurrentStoreDefined(): bool;

    /**
     * @param string $storeName
     *
     * @return \Generated\Shared\Transfer\StoreTransfer
     */
    public function getStoreByName($storeName);

    /**
     * @return array<\Generated\Shared\Transfer\StoreTransfer>
     */
    public function getAllStores(): array;
}
