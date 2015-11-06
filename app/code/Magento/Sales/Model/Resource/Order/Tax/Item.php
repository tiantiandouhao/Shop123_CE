<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Sales\Model\Resource\Order\Tax;

/**
 * Sales order tax resource model
 */
class Item extends \Magento\Framework\Model\Resource\Db\AbstractDb
{
    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('sales_order_tax_item', 'tax_item_id');
    }

    /**
     * Get Tax Items with order tax information
     *
     * @param int $orderId
     * @return array
     */
    public function getTaxItemsByOrderId($orderId)
    {
        $adapter = $this->_getReadAdapter();
        $select = $adapter->select()->from(
            ['item' => $this->getTable('sales_order_tax_item')],
            [
                'tax_id',
                'tax_percent',
                'item_id',
                'taxable_item_type',
                'associated_item_id',
                'real_amount',
                'real_base_amount',
            ]
        )->join(
            ['tax' => $this->getTable('sales_order_tax')],
            'item.tax_id = tax.tax_id',
            ['code', 'title', 'order_id']
        )->where(
            'tax.order_id = ?',
            $orderId
        );

        return $adapter->fetchAll($select);
    }
}
