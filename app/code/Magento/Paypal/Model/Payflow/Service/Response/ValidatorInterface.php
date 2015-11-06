<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Paypal\Model\Payflow\Service\Response;

use Magento\Framework\Object;

/**
 * Interface ValidatorInterface
 */
interface ValidatorInterface
{
    /**
     * Validate data
     *
     * @param Object $response
     * @return bool
     */
    public function validate(Object $response);
}
