<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Reports\Test\Unit\Controller\Adminhtml\Report\Product;

use Magento\Reports\Controller\Adminhtml\Report\Product\Sold;
use Magento\Framework\Object;
use Magento\Framework\Phrase;

class SoldTest extends \Magento\Reports\Test\Unit\Controller\Adminhtml\Report\AbstractControllerTest
{
    /**
     * @var \Magento\Reports\Controller\Adminhtml\Report\Product\Sold
     */
    protected $sold;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\Filter\Date|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dateMock;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->dateMock = $this->getMockBuilder('Magento\Framework\Stdlib\DateTime\Filter\Date')
            ->disableOriginalConstructor()
            ->getMock();

        $this->sold = new Sold(
            $this->contextMock,
            $this->fileFactoryMock,
            $this->dateMock
        );
    }

    /**
     * @return void
     */
    public function testExecute()
    {
        $titleMock = $this->getMockBuilder('Magento\Framework\View\Page\Title')
            ->disableOriginalConstructor()
            ->getMock();

        $titleMock
            ->expects($this->once())
            ->method('prepend')
            ->with(new Phrase('Ordered Products Report'));

        $this->viewMock
            ->expects($this->once())
            ->method('getPage')
            ->willReturn(
                new Object(
                    ['config' => new Object(
                        ['title' => $titleMock]
                    )]
                )
            );

        $this->menuBlockMock
            ->expects($this->once())
            ->method('setActive')
            ->with('Magento_Reports::report_products_sold');

        $this->breadcrumbsBlockMock
            ->expects($this->exactly(3))
            ->method('addLink')
            ->withConsecutive(
                [new Phrase('Reports'), new Phrase('Reports')],
                [new Phrase('Products'), new Phrase('Products')],
                [new Phrase('Products Ordered'), new Phrase('Products Ordered')]
            );

        $this->sold->execute();
    }
}
