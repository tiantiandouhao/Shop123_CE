<?php
/**
 * \Magento\Customer\Model\Resource\Customer\Collection
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Customer\Model\Resource\Customer;

class CollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\Customer\Model\Resource\Customer\Collection
     */
    protected $_collection;

    public function setUp()
    {
        $this->_collection = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
            'Magento\Customer\Model\Resource\Customer\Collection'
        );
    }

    public function testAddNameToSelect()
    {
        $this->_collection->addNameToSelect();
        $joinParts = $this->_collection->getSelect()->getPart(\Zend_Db_Select::FROM);

        $this->assertArrayHasKey('e', $joinParts);
        $this->assertCount(1, $joinParts);
    }
}
