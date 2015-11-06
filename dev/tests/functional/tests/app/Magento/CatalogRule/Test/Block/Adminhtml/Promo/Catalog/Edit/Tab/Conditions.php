<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogRule\Test\Block\Adminhtml\Promo\Catalog\Edit\Tab;

use Magento\Mtf\Factory\Factory;
use Magento\Backend\Test\Block\Widget\Tab;
use Magento\Mtf\Client\Element\SimpleElement;

/**
 * Class Conditions
 * Form Tab for specifying catalog price rule conditions
 *
 */
class Conditions extends Tab
{
    /**
     * Rule conditions block selector
     *
     * @var string
     */
    protected $ruleConditions = '#rule_conditions_fieldset';

    /**
     * Fill condition options
     *
     * @param array $fields
     * @param SimpleElement|null $element
     * @return void
     */
    public function fillFormTab(array $fields, SimpleElement $element = null)
    {
        $data = $this->dataMapping($fields);

        $conditionsBlock = Factory::getBlockFactory()->getMagentoCatalogRuleConditions(
            $element->find($this->ruleConditions)
        );
        $conditionsBlock->clickAddNew();

        $conditionsBlock->selectCondition($data['condition_type']['value']);
        $conditionsBlock->clickEllipsis();
        $conditionsBlock->selectConditionValue($data['condition_value']['value']);
    }
}
