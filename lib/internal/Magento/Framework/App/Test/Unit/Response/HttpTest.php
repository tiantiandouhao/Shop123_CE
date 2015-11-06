<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Magento\Framework\App\Test\Unit\Response;

use \Magento\Framework\App\Response\Http;

class HttpTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Http
     */
    protected $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Stdlib\CookieManagerInterface
     */
    protected $cookieManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     */
    protected $cookieMetadataFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\App\Http\Context
     */
    protected $contextMock;

    /** @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\App\Http\Context */
    protected $dateTimeMock;

    protected function setUp()
    {
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->cookieMetadataFactoryMock = $this->getMockBuilder(
            'Magento\Framework\Stdlib\Cookie\CookieMetadataFactory'
        )->disableOriginalConstructor()->getMock();
        $this->cookieManagerMock = $this->getMock('Magento\Framework\Stdlib\CookieManagerInterface');
        $this->contextMock = $this->getMockBuilder('Magento\Framework\App\Http\Context')->disableOriginalConstructor()
            ->getMock();

        $this->dateTimeMock = $this->getMockBuilder('Magento\Framework\Stdlib\DateTime')
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = $objectManager->getObject(
            'Magento\Framework\App\Response\Http',
            [
                'cookieManager' => $this->cookieManagerMock,
                'cookieMetadataFactory' => $this->cookieMetadataFactoryMock,
                'context' => $this->contextMock,
                'dateTime' => $this->dateTimeMock
            ]
        );
        $this->model->headersSentThrowsException = false;
        $this->model->setHeader('Name', 'Value');
    }

    protected function tearDown()
    {
        unset($this->model);
        $magentoObjectManagerFactory = \Magento\Framework\App\Bootstrap::createObjectManagerFactory(BP, $_SERVER);
        $objectManager = $magentoObjectManagerFactory->create($_SERVER);
        \Magento\Framework\App\ObjectManager::setInstance($objectManager);
    }

    public function testSendVary()
    {
        $data = ['some-vary-key' => 'some-vary-value'];
        $expectedCookieName = Http::COOKIE_VARY_STRING;
        $expectedCookieValue = sha1(serialize($data));
        $sensitiveCookieMetadataMock = $this->getMockBuilder('Magento\Framework\Stdlib\Cookie\SensitiveCookieMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $sensitiveCookieMetadataMock->expects($this->once())
            ->method('setPath')
            ->with('/')
            ->will($this->returnSelf());

        $this->contextMock->expects($this->once())
            ->method('getVaryString')
            ->with()
            ->will($this->returnValue($expectedCookieValue));

        $this->cookieMetadataFactoryMock->expects($this->once())
            ->method('createSensitiveCookieMetadata')
            ->with()
            ->will($this->returnValue($sensitiveCookieMetadataMock));

        $this->cookieManagerMock->expects($this->once())
            ->method('setSensitiveCookie')
            ->with($expectedCookieName, $expectedCookieValue, $sensitiveCookieMetadataMock);
        $this->model->sendVary();
    }

    public function testSendVaryEmptyData()
    {
        $expectedCookieName = Http::COOKIE_VARY_STRING;
        $cookieMetadataMock = $this->getMock('Magento\Framework\Stdlib\Cookie\CookieMetadata');
        $cookieMetadataMock->expects($this->once())
            ->method('setPath')
            ->with('/')
            ->will($this->returnSelf());
        $this->contextMock->expects($this->once())
            ->method('getVaryString')
            ->with()
            ->will($this->returnValue(null));
        $this->cookieMetadataFactoryMock->expects($this->once())
            ->method('createCookieMetadata')
            ->with()
            ->will($this->returnValue($cookieMetadataMock));
        $this->cookieManagerMock->expects($this->once())
            ->method('deleteCookie')
            ->with($expectedCookieName, $cookieMetadataMock);
        $this->model->sendVary();
    }

    /**
     * Test setting public cache headers
     */
    public function testSetPublicHeaders()
    {
        $ttl = 120;
        $timestamp = 1000000;
        $pragma = 'cache';
        $cacheControl = 'max-age=' . $ttl . ', public, s-maxage=' . $ttl;
        $expiresResult ='Thu, 01 Jan 1970 00:00:00 GMT';

        $this->dateTimeMock->expects($this->once())
            ->method('strToTime')
            ->with('+' . $ttl . ' seconds')
            ->willReturn($timestamp);
        $this->dateTimeMock->expects($this->once())
            ->method('gmDate')
            ->with(Http::EXPIRATION_TIMESTAMP_FORMAT, $timestamp)
            ->willReturn($expiresResult);

        $this->model->setPublicHeaders($ttl);
        $this->assertEquals($pragma, $this->model->getHeader('Pragma')->getFieldValue());
        $this->assertEquals($cacheControl, $this->model->getHeader('Cache-Control')->getFieldValue());
        $this->assertSame($expiresResult, $this->model->getHeader('Expires')->getFieldValue());
    }

    /**
     * Test for setting public headers without time to live parameter
     */
    public function testSetPublicHeadersWithoutTtl()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Time to live is a mandatory parameter for set public headers'
        );
        $this->model->setPublicHeaders(null);
    }

    /**
     * Test setting public cache headers
     */
    public function testSetPrivateHeaders()
    {
        $ttl = 120;
        $timestamp = 1000000;
        $pragma = 'cache';
        $cacheControl = 'max-age=' . $ttl . ', private';
        $expiresResult ='Thu, 01 Jan 1970 00:00:00 GMT';

        $this->dateTimeMock->expects($this->once())
            ->method('strToTime')
            ->with('+' . $ttl . ' seconds')
            ->willReturn($timestamp);
        $this->dateTimeMock->expects($this->once())
            ->method('gmDate')
            ->with(Http::EXPIRATION_TIMESTAMP_FORMAT, $timestamp)
            ->willReturn($expiresResult);

        $this->model->setPrivateHeaders($ttl);
        $this->assertEquals($pragma, $this->model->getHeader('Pragma')->getFieldValue());
        $this->assertEquals($cacheControl, $this->model->getHeader('Cache-Control')->getFieldValue());
        $this->assertEquals($expiresResult, $this->model->getHeader('Expires')->getFieldValue());
    }

    /**
     * Test for setting public headers without time to live parameter
     */
    public function testSetPrivateHeadersWithoutTtl()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Time to live is a mandatory parameter for set private headers'
        );
        $this->model->setPrivateHeaders(null);
    }

    /**
     * Test setting public cache headers
     */
    public function testSetNoCacheHeaders()
    {
        $timestamp = 1000000;
        $pragma = 'no-cache';
        $cacheControl = 'max-age=0, must-revalidate, no-cache, no-store';
        $expiresResult ='Thu, 01 Jan 1970 00:00:00 GMT';

        $this->dateTimeMock->expects($this->once())
            ->method('strToTime')
            ->with('-1 year')
            ->willReturn($timestamp);
        $this->dateTimeMock->expects($this->once())
            ->method('gmDate')
            ->with(Http::EXPIRATION_TIMESTAMP_FORMAT, $timestamp)
            ->willReturn($expiresResult);

        $this->model->setNoCacheHeaders();
        $this->assertEquals($pragma, $this->model->getHeader('Pragma')->getFieldValue());
        $this->assertEquals($cacheControl, $this->model->getHeader('Cache-Control')->getFieldValue());
        $this->assertEquals($expiresResult, $this->model->getHeader('Expires')->getFieldValue());
    }

    /**
     * Test setting body in JSON format
     */
    public function testRepresentJson()
    {
        $this->model->setHeader('Content-Type', 'text/javascript');
        $this->model->representJson('json_string');
        $this->assertEquals('application/json', $this->model->getHeader('Content-Type')->getFieldValue());
        $this->assertEquals('json_string', $this->model->getBody('default'));
    }

    /**
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage ObjectManager isn't initialized
     */
    public function testWakeUpWithException()
    {
        /* ensure that the test preconditions are met */
        $objectManagerClass = new \ReflectionClass('Magento\Framework\App\ObjectManager');
        $instanceProperty = $objectManagerClass->getProperty('_instance');
        $instanceProperty->setAccessible(true);
        $instanceProperty->setValue(null);

        $this->model->__wakeup();
        $this->assertNull($this->cookieMetadataFactoryMock);
        $this->assertNull($this->cookieManagerMock);
    }

    /**
     * Test for the magic method __wakeup
     *
     * @covers \Magento\Framework\App\Response\Http::__wakeup
     */
    public function testWakeUpWith()
    {
        $objectManagerMock = $this->getMock('Magento\Framework\App\ObjectManager', [], [], '', false);
        $objectManagerMock->expects($this->once())
            ->method('create')
            ->with('Magento\Framework\Stdlib\CookieManagerInterface')
            ->will($this->returnValue($this->cookieManagerMock));
        $objectManagerMock->expects($this->at(1))
            ->method('get')
            ->with('Magento\Framework\Stdlib\Cookie\CookieMetadataFactory')
            ->will($this->returnValue($this->cookieMetadataFactoryMock));

        \Magento\Framework\App\ObjectManager::setInstance($objectManagerMock);
        $this->model->__wakeup();
    }

    public function testSetXFrameOptions()
    {
        $value = 'DENY';
        $this->model->setXFrameOptions($value);
        $this->assertSame($value, $this->model->getHeader(Http::HEADER_X_FRAME_OPT)->getFieldValue());
    }
}
