<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Webapi\Controller;

class PathProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Webapi\Controller\PathProcessor
     */
    protected $pathProcessor;

    protected function setUp()
    {
        $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->storeManager = $objectManager->get('Magento\Store\Model\StoreManagerInterface');
        $this->pathProcessor = $objectManager->get('Magento\Webapi\Controller\PathProcessor');
    }

    /**
     * @magentoDataFixture Magento/Store/_files/core_fixturestore.php
     */
    public function testProcessWithValidStoreCode()
    {
        $storeCode = 'fixturestore';
        $basePath = "rest/{$storeCode}";
        $path = $basePath . '/V1/customerAccounts/createCustomer';
        $resultPath = $this->pathProcessor->process($path);
        $this->assertEquals(str_replace($basePath, "", $path), $resultPath);
        $this->assertEquals($storeCode, $this->storeManager->getStore()->getCode());
    }

    public function testProcessWithoutStoreCode()
    {
        $path = 'rest/V1/customerAccounts/createCustomer';
        $result = $this->pathProcessor->process($path);
        $this->assertEquals('/V1/customerAccounts/createCustomer', $result);
        $this->assertEquals('default', $this->storeManager->getStore()->getCode());
    }
}
