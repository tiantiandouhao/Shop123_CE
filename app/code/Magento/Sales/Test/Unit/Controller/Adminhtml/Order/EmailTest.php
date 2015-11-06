<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Sales\Test\Unit\Controller\Adminhtml\Order;

use \Magento\Sales\Controller\Adminhtml\Order\Email;

use Magento\Framework\App\Action\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

/**
 * Class EmailTest
 *
 * @package Magento\Sales\Controller\Adminhtml\Order
 */
class EmailTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Email
     */
    protected $orderEmail;

    /**
     * @var Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    /**
     * @var \Magento\Backend\Model\View\Result\Redirect|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultRedirect;

    /**
     * @var \Magento\Framework\App\Request\Http|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $request;

    /**
     * @var \Magento\Framework\App\ResponseInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $response;

    /**
     * @var \Magento\Framework\Message\Manager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\ObjectManager\ObjectManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManager;

    /**
     * @var \Magento\Backend\Model\Session|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $session;

    /**
     * @var \Magento\Framework\App\ActionFlag|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $actionFlag;

    /**
     * @var \Magento\Backend\Helper\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $helper;

    public function setUp()
    {
        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->context = $this->getMock(
            'Magento\Backend\App\Action\Context',
            [
                'getRequest',
                'getResponse',
                'getMessageManager',
                'getRedirect',
                'getObjectManager',
                'getSession',
                'getActionFlag',
                'getHelper',
                'getResultRedirectFactory'
            ],
            [],
            '',
            false
        );
        $resultRedirectFactory = $this->getMock(
            'Magento\Backend\Model\View\Result\RedirectFactory',
            ['create'],
            [],
            '',
            false
        );
        $this->response = $this->getMock(
            'Magento\Framework\App\ResponseInterface',
            ['setRedirect', 'sendResponse'],
            [],
            '',
            false
        );
        $this->request = $this->getMockBuilder('Magento\Framework\App\Request\Http')
            ->disableOriginalConstructor()->getMock();
        $this->objectManager = $this->getMock(
            'Magento\Framework\ObjectManager\ObjectManager',
            ['create'],
            [],
            '',
            false
        );
        $this->messageManager = $this->getMock(
            'Magento\Framework\Message\Manager',
            ['addSuccess', 'addError'],
            [],
            '',
            false
        );
        $this->session = $this->getMock('Magento\Backend\Model\Session', ['setIsUrlNotice'], [], '', false);
        $this->actionFlag = $this->getMock('Magento\Framework\App\ActionFlag', ['get', 'set'], [], '', false);
        $this->helper = $this->getMock('\Magento\Backend\Helper\Data', ['getUrl'], [], '', false);
        $this->resultRedirect = $this->getMock('Magento\Backend\Model\View\Result\Redirect', [], [], '', false);
        $resultRedirectFactory->expects($this->any())->method('create')->willReturn($this->resultRedirect);

        $this->context->expects($this->once())->method('getMessageManager')->willReturn($this->messageManager);
        $this->context->expects($this->once())->method('getRequest')->willReturn($this->request);
        $this->context->expects($this->once())->method('getResponse')->willReturn($this->response);
        $this->context->expects($this->once())->method('getObjectManager')->willReturn($this->objectManager);
        $this->context->expects($this->once())->method('getSession')->willReturn($this->session);
        $this->context->expects($this->once())->method('getActionFlag')->willReturn($this->actionFlag);
        $this->context->expects($this->once())->method('getHelper')->willReturn($this->helper);
        $this->context->expects($this->once())->method('getResultRedirectFactory')->willReturn($resultRedirectFactory);

        $this->orderEmail = $objectManagerHelper->getObject(
            'Magento\Sales\Controller\Adminhtml\Order\Email',
            [
                'context' => $this->context,
                'request' => $this->request,
                'response' => $this->response
            ]
        );
    }

    public function testEmail()
    {
        $orderId = 10000031;
        $orderClassName = 'Magento\Sales\Model\Order';
        $orderNotifierClassName = 'Magento\Sales\Model\OrderNotifier';
        $order = $this->getMock($orderClassName, ['load', 'getId', '__wakeup'], [], '', false);
        $cmNotifier = $this->getMock($orderNotifierClassName, ['notify', '__wakeup'], [], '', false);

        $this->request->expects($this->once())
            ->method('getParam')
            ->with('order_id')
            ->will($this->returnValue($orderId));
        $this->objectManager->expects($this->at(0))
            ->method('create')
            ->with($orderClassName)
            ->will($this->returnValue($order));
        $order->expects($this->once())
            ->method('load')
            ->with($orderId)
            ->will($this->returnSelf());
        $order->expects($this->atLeastOnce())
            ->method('getId')
            ->will($this->returnValue($orderId));
        $this->objectManager->expects($this->at(1))
            ->method('create')
            ->with($orderNotifierClassName)
            ->will($this->returnValue($cmNotifier));
        $cmNotifier->expects($this->once())
            ->method('notify')
            ->will($this->returnValue(true));
        $this->messageManager->expects($this->once())
            ->method('addSuccess')
            ->with('You sent the order email.');
        $this->resultRedirect->expects($this->once())
            ->method('setPath')
            ->with('sales/order/view', ['order_id' => $orderId])
            ->willReturnSelf();

        $this->assertInstanceOf(
            'Magento\Backend\Model\View\Result\Redirect',
            $this->orderEmail->execute()
        );
        $this->assertEquals($this->response, $this->orderEmail->getResponse());
    }

    public function testEmailNoOrderId()
    {
        $orderClassName = 'Magento\Sales\Model\Order';
        $order = $this->getMock($orderClassName, ['load', 'getId', '__wakeup'], [], '', false);
        $this->request->expects($this->once())
            ->method('getParam')
            ->with('order_id')
            ->will($this->returnValue(null));

        $this->objectManager->expects($this->at(0))
            ->method('create')
            ->with($orderClassName)
            ->will($this->returnValue($order));
        $order->expects($this->once())
            ->method('load')
            ->with(null)
            ->will($this->returnSelf());
        $this->messageManager->expects($this->once())
            ->method('addError')
            ->with('This order no longer exists.');

        $this->actionFlag->expects($this->once())
            ->method('set')
            ->with('', 'no-dispatch', true)
            ->will($this->returnValue(true));
        $this->resultRedirect->expects($this->once())
            ->method('setPath')
            ->with('sales/*/')
            ->willReturnSelf();

        $this->assertInstanceOf(
            'Magento\Backend\Model\View\Result\Redirect',
            $this->orderEmail->execute()
        );
    }
}
