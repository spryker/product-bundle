<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ProductBundle\Business\ProductBundle\Stock;

interface ProductBundleStockHandlerInterface
{
    public function updateAffectedBundlesStock(string $bundledProductSku): void;
}
