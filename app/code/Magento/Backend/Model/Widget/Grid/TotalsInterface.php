<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Backend\Model\Widget\Grid;

interface TotalsInterface
{
    /**
     * Return object contains totals for all items in collection
     *
     * @abstract
     * @param \Magento\Framework\Data\Collection $collection
     * @return \Magento\Framework\Object
     * @api
     */
    public function countTotals($collection);
}
