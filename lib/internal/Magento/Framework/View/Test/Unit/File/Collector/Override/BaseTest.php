<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Magento\Framework\View\Test\Unit\File\Collector\Override;

use \Magento\Framework\View\File\Collector\Override\Base;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Directory\Read;
use Magento\Framework\View\File\Factory;

class BaseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Base
     */
    private $model;

    /**
     * @var Read | \PHPUnit_Framework_MockObject_MockObject
     */
    private $directory;

    /**
     * @var Factory | \PHPUnit_Framework_MockObject_MockObject
     */
    private $fileFactory;

    protected function setUp()
    {
        $this->directory = $this->getMock(
            'Magento\Framework\Filesystem\Directory\Read',
            ['getAbsolutePath', 'search'], [], '', false
        );
        $filesystem = $this->getMock(
            'Magento\Framework\Filesystem', ['getDirectoryRead'], [], '', false
        );
        $filesystem->expects($this->once())
            ->method('getDirectoryRead')
            ->with(DirectoryList::THEMES)
            ->will($this->returnValue($this->directory));
        $this->fileFactory = $this->getMock('Magento\Framework\View\File\Factory', [], [], '', false);
        $this->model = new \Magento\Framework\View\File\Collector\Override\Base(
            $filesystem, $this->fileFactory, 'override'
        );
    }

    /**
     * @param array $files
     * @param string $filePath
     *
     * @dataProvider dataProvider
     */
    public function testGetFiles($files, $filePath)
    {
        $theme = $this->getMockForAbstractClass('Magento\Framework\View\Design\ThemeInterface');
        $theme->expects($this->once())->method('getFullPath')->will($this->returnValue('area/theme/path'));

        $handlePath = 'design/area/theme/path/%s/override/%s';
        $returnKeys = [];
        foreach ($files as $file) {
            $returnKeys[] = sprintf($handlePath, $file['module'], $file['handle']);
        }

        $this->directory->expects($this->once())
            ->method('search')
            ->will($this->returnValue($returnKeys));
        $this->directory->expects($this->any())
            ->method('getAbsolutePath')
            ->will($this->returnArgument(0));

        $checkResult = [];
        foreach ($files as $key => $file) {
            $checkResult[$key] = new \Magento\Framework\View\File($file['handle'], $file['module']);
            $this->fileFactory
                ->expects($this->at($key))
                ->method('create')
                ->with(sprintf($handlePath, $file['module'], $file['handle']), $file['module'])
                ->will($this->returnValue($checkResult[$key]));
        }

        $this->assertSame($checkResult, $this->model->getFiles($theme, $filePath));
    }

    /**
     * @return array
     */
    public function dataProvider()
    {
        return [
            [
                [
                    ['handle' => '1.xml', 'module' => 'Module_One'],
                    ['handle' => '2.xml', 'module' => 'Module_One'],
                    ['handle' => '3.xml', 'module' => 'Module_Two'],
                ],
                '*.xml',
            ],
            [
                [
                    ['handle' => 'preset/4', 'module' => 'Module_Fourth'],
                ],
                'preset/4',
            ],
        ];
    }
}
