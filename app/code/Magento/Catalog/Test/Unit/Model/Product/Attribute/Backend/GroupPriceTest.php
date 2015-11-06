<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Catalog\Test\Unit\Model\Product\Attribute\Backend;

class GroupPriceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\Catalog\Model\Product\Attribute\Backend\GroupPrice
     */
    protected $model;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectHelper;

    protected function setUp()
    {
        $this->objectHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->model = $this->objectHelper->getObject('Magento\Catalog\Model\Product\Attribute\Backend\GroupPrice');
    }

    public function testIsScaler()
    {
        $this->assertFalse($this->model->isScalar(), 'Attribute GroupPrice should not be scaler');
    }
}
