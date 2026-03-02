<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\ProductBundle\ItemOperation;

use Generated\Shared\Transfer\QuoteTransfer;

interface ItemQuantityCounterInterface
{
    public function getItemCount(QuoteTransfer $quoteTransfer): int;
}
