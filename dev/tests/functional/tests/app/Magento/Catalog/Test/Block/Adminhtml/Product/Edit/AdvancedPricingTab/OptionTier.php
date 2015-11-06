<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Catalog\Test\Block\Adminhtml\Product\Edit\AdvancedPricingTab;

use Magento\Catalog\Test\Block\Adminhtml\Product\Edit\Tab\Options\AbstractOptions;
use Magento\Mtf\Client\Element\SimpleElement;

/**
 * Class OptionTier
 * Form 'Tier prices' on the 'Advanced Pricing' tab
 */
class OptionTier extends AbstractOptions
{
    /**
     * 'Add Tier' button selector
     *
     * @var string
     */
    protected $buttonFormLocator = "#tiers_table tfoot button";

    /**
     * Fill product form 'Tier price'
     *
     * @param array $fields
     * @param SimpleElement $element
     * @return $this
     */
    public function fillOptions(array $fields, SimpleElement $element = null)
    {
        $this->_rootElement->find($this->buttonFormLocator)->click();
        return parent::fillOptions($fields, $element);
    }
}
