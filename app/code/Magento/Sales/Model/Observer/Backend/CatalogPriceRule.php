<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Sales\Model\Observer\Backend;

class CatalogPriceRule
{
    /**
     * @var \Magento\Quote\Model\Resource\Quote
     */
    protected $_quote;

    /**
     * @param \Magento\Quote\Model\Resource\Quote $quote
     */
    public function __construct(\Magento\Quote\Model\Resource\Quote $quote)
    {
        $this->_quote = $quote;
    }

    /**
     * When applying a catalog price rule, make related quotes recollect on demand
     *
     * @return void
     */
    public function dispatch()
    {
        $this->_quote->markQuotesRecollectOnCatalogRules();
    }
}
