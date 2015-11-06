<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\Config\Test\Unit;

class ViewTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\Framework\Config\View
     */
    protected $_model = null;

    protected function setUp()
    {
        $this->_model = new \Magento\Framework\Config\View(
            [
                file_get_contents(__DIR__ . '/_files/view_one.xml'),
                file_get_contents(__DIR__ . '/_files/view_two.xml'),
            ]
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testConstructException()
    {
        new \Magento\Framework\Config\View([]);
    }

    public function testGetSchemaFile()
    {
        $this->assertFileExists($this->_model->getSchemaFile());
    }

    public function testGetVars()
    {
        $this->assertEquals(['one' => 'Value One', 'two' => 'Value Two'], $this->_model->getVars('Two'));
    }

    public function testGetVarValue()
    {
        $this->assertFalse($this->_model->getVarValue('Unknown', 'nonexisting'));
        $this->assertEquals('Value One', $this->_model->getVarValue('Two', 'one'));
        $this->assertEquals('Value Two', $this->_model->getVarValue('Two', 'two'));
        $this->assertEquals('Value Three', $this->_model->getVarValue('Three', 'three'));
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     */
    public function testInvalidXml()
    {
        new \Magento\Framework\Config\View([file_get_contents(__DIR__ . '/_files/view_invalid.xml')]);
    }

    public function testGetExcludedFiles()
    {
        $this->assertEquals(2, count($this->_model->getExcludedFiles()));
    }

    public function testGetExcludedDir()
    {
        $this->assertEquals(1, count($this->_model->getExcludedDir()));
    }
}
