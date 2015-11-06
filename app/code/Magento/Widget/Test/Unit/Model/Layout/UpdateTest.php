<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Widget\Test\Unit\Model\Layout;

class UpdateTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test formatted time data
     */
    const TEST_FORMATTED_TIME = 'test_time';

    public function testBeforeSave()
    {
        $resourceModel = $this->getMock(
            'Magento\Widget\Model\Resource\Layout\Update',
            [
                '__wakeup',
                'formatDate',
                'getIdFieldName',
                'beginTransaction',
                'save',
                'addCommitCallback',
                'commit'
            ],
            [],
            '',
            false
        );
        $dateTime = $this->getMock('\Magento\Framework\Stdlib\DateTime', [], []);
        $dateTime->expects(
            $this->once()
        )->method(
            'formatDate'
        )->with(
            $this->isType('int')
        )->will(
            $this->returnValue(self::TEST_FORMATTED_TIME)
        );

        $helper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        /** @var $model \Magento\Widget\Model\Layout\Update */
        $model = $helper->getObject(
            'Magento\Widget\Model\Layout\Update',
            ['resource' => $resourceModel, 'dateTime' => $dateTime]
        );
        $model->setId(0);
        // set any data to set _hasDataChanges flag
        $model->beforeSave();

        $this->assertEquals(self::TEST_FORMATTED_TIME, $model->getUpdatedAt());
    }
}
