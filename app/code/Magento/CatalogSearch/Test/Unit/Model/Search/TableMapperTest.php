<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogSearch\Test\Unit\Model\Search;

use Magento\Framework\Search\Request\FilterInterface;
use Magento\Framework\Search\Request\QueryInterface;
use \Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Test for \Magento\CatalogSearch\Model\Search\TableMapper
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TableMapperTest extends \PHPUnit_Framework_TestCase
{
    const WEBSITE_ID = 4512;
    const STORE_ID = 2514;

    /**
     * @var \Magento\Catalog\Model\Resource\Product\Attribute\Collection|\PHPUnit_Framework_MockObject_MockObject
     */
    private $attributeCollection;

    /**
     * @var \Magento\Store\Model\Website|\PHPUnit_Framework_MockObject_MockObject
     */
    private $website;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $connection;

    /**
     * @var \Magento\Framework\Search\RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $request;

    /**
     * @var \Magento\Framework\DB\Select|\PHPUnit_Framework_MockObject_MockObject
     */
    private $select;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $storeManager;

    /**
     * @var \Magento\Framework\App\Resource|\PHPUnit_Framework_MockObject_MockObject
     */
    private $resource;

    /**
     * @var \Magento\Store\Model\Store|\PHPUnit_Framework_MockObject_MockObject
     */
    private $store;

    /**
     * @var \Magento\CatalogSearch\Model\Search\TableMapper
     */
    private $target;

    protected function setUp()
    {
        $objectManager = new ObjectManager($this);

        $this->connection = $this->getMockBuilder('\Magento\Framework\DB\Adapter\AdapterInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->connection->expects($this->any())
            ->method('quoteInto')
            ->willReturnCallback(
                function ($query, $expression) {
                    return str_replace('?', $expression, $query);
                }
            );

        $this->resource = $this->getMockBuilder('\Magento\Framework\App\Resource')
            ->disableOriginalConstructor()
            ->getMock();
        $this->resource->method('getTableName')
            ->willReturnCallback(
                function ($table) {
                    return 'prefix_' . $table;
                }
            );
        $this->resource->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connection);

        $this->website = $this->getMockBuilder('\Magento\Store\Model\Website')
            ->disableOriginalConstructor()
            ->getMock();
        $this->website->expects($this->any())
            ->method('getId')
            ->willReturn(self::WEBSITE_ID);
        $this->store = $this->getMockBuilder('\Magento\Store\Model\Store')
            ->disableOriginalConstructor()
            ->getMock();
        $this->store->expects($this->any())
            ->method('getId')
            ->willReturn(self::STORE_ID);
        $this->storeManager = $this->getMockBuilder('\Magento\Store\Model\StoreManagerInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManager->expects($this->any())
            ->method('getWebsite')
            ->willReturn($this->website);
        $this->storeManager->expects($this->any())
            ->method('getStore')
            ->willReturn($this->store);
        $this->attributeCollection = $this->getMockBuilder(
            '\Magento\Catalog\Model\Resource\Product\Attribute\Collection'
        )
            ->disableOriginalConstructor()
            ->getMock();
        $attributeCollectionFactory = $this->getMockBuilder(
            '\Magento\Catalog\Model\Resource\Product\Attribute\CollectionFactory'
        )
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $attributeCollectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->attributeCollection);
        $this->target = $objectManager->getObject(
            '\Magento\CatalogSearch\Model\Search\TableMapper',
            [
                'resource' => $this->resource,
                'storeManager' => $this->storeManager,
                'attributeCollectionFactory' => $attributeCollectionFactory
            ]
        );

        $this->select = $this->getMockBuilder('\Magento\Framework\DB\Select')
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMockBuilder('\Magento\Framework\Search\RequestInterface')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testAddPriceFilter()
    {
        $priceFilter = $this->createRangeFilter('price');
        $query = $this->createFilterQuery($priceFilter);
        $this->request->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $this->select->expects($this->once())
            ->method('joinLeft')
            ->with(
                ['price_index' => 'prefix_catalog_product_index_price'],
                'search_index.entity_id = price_index.entity_id AND price_index.website_id = ' . self::WEBSITE_ID,
                []
            )
            ->willReturnSelf();
        $select = $this->target->addTables($this->select, $this->request);
        $this->assertEquals($this->select, $select, 'Returned results isn\'t equal to passed select');
    }

    public function testAddStaticAttributeFilter()
    {
        $priceFilter = $this->createRangeFilter('static');
        $query = $this->createFilterQuery($priceFilter);
        $this->createAttributeMock('static', 'static', 'backend_table', 0);
        $this->request->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $this->select->expects($this->once())
            ->method('joinLeft')
            ->with(
                ['4111c4a3daddb5c5dba31cdac705114b' => 'backend_table'],
                'search_index.entity_id = 4111c4a3daddb5c5dba31cdac705114b.entity_id',
                null
            )
            ->willReturnSelf();
        $select = $this->target->addTables($this->select, $this->request);
        $this->assertEquals($this->select, $select, 'Returned results isn\'t equal to passed select');
    }

    public function testAddCategoryIds()
    {
        $categoryIdsFilter = $this->createTermFilter('category_ids');
        $query = $this->createFilterQuery($categoryIdsFilter);
        $this->request->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $this->select->expects($this->once())
            ->method('joinLeft')
            ->with(
                ['category_ids_index' => 'prefix_catalog_category_product_index'],
                'search_index.entity_id = category_ids_index.product_id',
                []
            )
            ->willReturnSelf();
        $select = $this->target->addTables($this->select, $this->request);
        $this->assertEquals($this->select, $select, 'Returned results isn\'t equal to passed select');
    }

    public function testAddTermFilter()
    {
        $this->createAttributeMock('color', null, null, 132, 0);
        $categoryIdsFilter = $this->createTermFilter('color');
        $query = $this->createFilterQuery($categoryIdsFilter);
        $this->request->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $this->select->expects($this->once())
            ->method('joinLeft')
            ->with(
                ['color_filter' => 'prefix_catalog_product_index_eav'],
                'search_index.entity_id = color_filter.entity_id'
                . ' AND color_filter.attribute_id = 132'
                . ' AND color_filter.store_id = 2514',
                []
            )
            ->willReturnSelf();
        $select = $this->target->addTables($this->select, $this->request);
        $this->assertEquals($this->select, $select, 'Returned results isn\'t equal to passed select');
    }

    public function testAddBoolQueryWithTermFiltersInside()
    {
        $this->createAttributeMock('must1', null, null, 101, 0);
        $this->createAttributeMock('should1', null, null, 102, 1);
        $this->createAttributeMock('mustNot1', null, null, 103, 2);

        $query = $this->createBoolQuery(
            [
                $this->createFilterQuery($this->createTermFilter('must1')),
            ],
            [
                $this->createFilterQuery($this->createTermFilter('should1')),
            ],
            [
                $this->createFilterQuery($this->createTermFilter('mustNot1')),
            ]
        );
        $this->request->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $this->select->expects($this->at(0))
            ->method('joinLeft')
            ->with(
                ['must1_filter' => 'prefix_catalog_product_index_eav'],
                'search_index.entity_id = must1_filter.entity_id'
                . ' AND must1_filter.attribute_id = 101'
                . ' AND must1_filter.store_id = 2514',
                []
            )
            ->willReturnSelf();
        $this->select->expects($this->at(1))
            ->method('joinLeft')
            ->with(
                ['should1_filter' => 'prefix_catalog_product_index_eav'],
                'search_index.entity_id = should1_filter.entity_id'
                . ' AND should1_filter.attribute_id = 102'
                . ' AND should1_filter.store_id = 2514',
                []
            )
            ->willReturnSelf();
        $this->select->expects($this->at(2))
            ->method('joinLeft')
            ->with(
                ['mustNot1_filter' => 'prefix_catalog_product_index_eav'],
                'search_index.entity_id = mustNot1_filter.entity_id'
                . ' AND mustNot1_filter.attribute_id = 103'
                . ' AND mustNot1_filter.store_id = 2514',
                []
            )
            ->willReturnSelf();
        $select = $this->target->addTables($this->select, $this->request);
        $this->assertEquals($this->select, $select, 'Returned results isn\'t equal to passed select');
    }

    public function testAddBoolQueryWithTermAndPriceFiltersInside()
    {
        $this->createAttributeMock('must1', null, null, 101, 0);
        $this->createAttributeMock('should1', null, null, 102, 1);
        $this->createAttributeMock('mustNot1', null, null, 103, 2);
        $query = $this->createBoolQuery(
            [
                $this->createFilterQuery($this->createTermFilter('must1')),
                $this->createFilterQuery($this->createRangeFilter('price')),
            ],
            [
                $this->createFilterQuery($this->createTermFilter('should1')),
            ],
            [
                $this->createFilterQuery($this->createTermFilter('mustNot1')),
            ]
        );
        $this->request->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $this->select->expects($this->at(0))
            ->method('joinLeft')
            ->with(
                ['must1_filter' => 'prefix_catalog_product_index_eav'],
                'search_index.entity_id = must1_filter.entity_id'
                . ' AND must1_filter.attribute_id = 101'
                . ' AND must1_filter.store_id = 2514',
                []
            )
            ->willReturnSelf();
        $this->select->expects($this->at(1))
            ->method('joinLeft')
            ->with(
                ['price_index' => 'prefix_catalog_product_index_price'],
                'search_index.entity_id = price_index.entity_id AND price_index.website_id = ' . self::WEBSITE_ID,
                []
            )
            ->willReturnSelf();
        $this->select->expects($this->at(2))
            ->method('joinLeft')
            ->with(
                ['should1_filter' => 'prefix_catalog_product_index_eav'],
                'search_index.entity_id = should1_filter.entity_id'
                . ' AND should1_filter.attribute_id = 102'
                . ' AND should1_filter.store_id = 2514',
                []
            )
            ->willReturnSelf();
        $this->select->expects($this->at(3))
            ->method('joinLeft')
            ->with(
                ['mustNot1_filter' => 'prefix_catalog_product_index_eav'],
                'search_index.entity_id = mustNot1_filter.entity_id'
                . ' AND mustNot1_filter.attribute_id = 103'
                . ' AND mustNot1_filter.store_id = 2514',
                []
            )
            ->willReturnSelf();
        $select = $this->target->addTables($this->select, $this->request);
        $this->assertEquals($this->select, $select, 'Returned results isn\'t equal to passed select');
    }

    public function testAddBoolFilterWithTermFiltersInside()
    {
        $this->createAttributeMock('must1', null, null, 101, 0);
        $this->createAttributeMock('should1', null, null, 102, 1);
        $this->createAttributeMock('mustNot1', null, null, 103, 2);
        $query = $this->createFilterQuery(
            $this->createBoolFilter(
                [
                    $this->createTermFilter('must1'),
                ],
                [
                    $this->createTermFilter('should1'),
                ],
                [
                    $this->createTermFilter('mustNot1'),
                ]
            )
        );
        $this->request->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $this->select->expects($this->at(0))
            ->method('joinLeft')
            ->with(
                ['must1_filter' => 'prefix_catalog_product_index_eav'],
                'search_index.entity_id = must1_filter.entity_id'
                . ' AND must1_filter.attribute_id = 101'
                . ' AND must1_filter.store_id = 2514',
                []
            )
            ->willReturnSelf();
        $this->select->expects($this->at(1))
            ->method('joinLeft')
            ->with(
                ['should1_filter' => 'prefix_catalog_product_index_eav'],
                'search_index.entity_id = should1_filter.entity_id'
                . ' AND should1_filter.attribute_id = 102'
                . ' AND should1_filter.store_id = 2514',
                []
            )
            ->willReturnSelf();
        $this->select->expects($this->at(2))
            ->method('joinLeft')
            ->with(
                ['mustNot1_filter' => 'prefix_catalog_product_index_eav'],
                'search_index.entity_id = mustNot1_filter.entity_id'
                . ' AND mustNot1_filter.attribute_id = 103'
                . ' AND mustNot1_filter.store_id = 2514',
                []
            )
            ->willReturnSelf();
        $select = $this->target->addTables($this->select, $this->request);
        $this->assertEquals($this->select, $select, 'Returned results isn\'t equal to passed select');
    }

    public function testAddBoolFilterWithBoolFiltersInside()
    {
        $this->createAttributeMock('must1', null, null, 101, 0);
        $this->createAttributeMock('should1', null, null, 102, 1);
        $this->createAttributeMock('mustNot1', null, null, 103, 2);
        $query = $this->createFilterQuery(
            $this->createBoolFilter(
                [
                    $this->createBoolFilter([$this->createTermFilter('must1')], [], []),
                ],
                [
                    $this->createBoolFilter([$this->createTermFilter('should1')], [], []),
                ],
                [
                    $this->createBoolFilter([$this->createTermFilter('mustNot1')], [], []),
                ]
            )
        );
        $this->request->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $this->select->expects($this->at(0))
            ->method('joinLeft')
            ->with(
                ['must1_filter' => 'prefix_catalog_product_index_eav'],
                'search_index.entity_id = must1_filter.entity_id'
                . ' AND must1_filter.attribute_id = 101'
                . ' AND must1_filter.store_id = 2514',
                []
            )
            ->willReturnSelf();
        $this->select->expects($this->at(1))
            ->method('joinLeft')
            ->with(
                ['should1_filter' => 'prefix_catalog_product_index_eav'],
                'search_index.entity_id = should1_filter.entity_id'
                . ' AND should1_filter.attribute_id = 102'
                . ' AND should1_filter.store_id = 2514',
                []
            )
            ->willReturnSelf();
        $this->select->expects($this->at(2))
            ->method('joinLeft')
            ->with(
                ['mustNot1_filter' => 'prefix_catalog_product_index_eav'],
                'search_index.entity_id = mustNot1_filter.entity_id'
                . ' AND mustNot1_filter.attribute_id = 103'
                . ' AND mustNot1_filter.store_id = 2514',
                []
            )
            ->willReturnSelf();
        $select = $this->target->addTables($this->select, $this->request);
        $this->assertEquals($this->select, $select, 'Returned results isn\'t equal to passed select');
    }

    /**
     * @param $filter
     * @return \Magento\Framework\Search\Request\Query\Filter|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createFilterQuery($filter)
    {
        $query = $this->getMockBuilder('\Magento\Framework\Search\Request\Query\Filter')
            ->disableOriginalConstructor()
            ->getMock();
        $query->method('getType')
            ->willReturn(QueryInterface::TYPE_FILTER);
        $query->method('getReference')
            ->willReturn($filter);
        return $query;
    }

    /**
     * @param array $must
     * @param array $should
     * @param array $mustNot
     * @return \Magento\Framework\Search\Request\Query\Bool|\PHPUnit_Framework_MockObject_MockObject
     * @internal param $filter
     */
    private function createBoolQuery(array $must, array $should, array $mustNot)
    {
        $query = $this->getMockBuilder('\Magento\Framework\Search\Request\Query\Bool')
            ->disableOriginalConstructor()
            ->getMock();
        $query->method('getType')
            ->willReturn(QueryInterface::TYPE_BOOL);
        $query->method('getMust')
            ->willReturn($must);
        $query->method('getShould')
            ->willReturn($should);
        $query->method('getMustNot')
            ->willReturn($mustNot);
        return $query;
    }

    /**
     * @param array $must
     * @param array $should
     * @param array $mustNot
     * @return \Magento\Framework\Search\Request\Filter\Bool|\PHPUnit_Framework_MockObject_MockObject
     * @internal param $filter
     */
    private function createBoolFilter(array $must, array $should, array $mustNot)
    {
        $query = $this->getMockBuilder('\Magento\Framework\Search\Request\Filter\Bool')
            ->disableOriginalConstructor()
            ->getMock();
        $query->method('getType')
            ->willReturn(FilterInterface::TYPE_BOOL);
        $query->method('getMust')
            ->willReturn($must);
        $query->method('getShould')
            ->willReturn($should);
        $query->method('getMustNot')
            ->willReturn($mustNot);
        return $query;
    }

    /**
     * @param string $field
     * @return \Magento\Framework\Search\Request\Filter\Range|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createRangeFilter($field)
    {
        $filter = $this->createFilterMock(
            '\Magento\Framework\Search\Request\Filter\Range',
            FilterInterface::TYPE_RANGE,
            $field
        );
        return $filter;
    }

    /**
     * @param string $field
     * @return \Magento\Framework\Search\Request\Filter\Term|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createTermFilter($field)
    {
        $filter = $this->createFilterMock(
            '\Magento\Framework\Search\Request\Filter\Term',
            FilterInterface::TYPE_TERM,
            $field
        );
        return $filter;
    }

    /**
     * @param string $class
     * @param string $type
     * @param string $field
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function createFilterMock($class, $type, $field)
    {
        $filter = $this->getMockBuilder($class)
            ->disableOriginalConstructor()
            ->getMock();
        $filter->method('getType')
            ->willReturn($type);
        $filter->method('getField')
            ->willReturn($field);
        return $filter;
    }

    /**
     * @param string $code
     * @param string $backendType
     * @param string $backendTable
     * @param int $attributeId
     * @param int $positionInCollection
     */
    private function createAttributeMock(
        $code,
        $backendType = null,
        $backendTable = null,
        $attributeId = 120,
        $positionInCollection = 0
    ) {
        $attribute = $this->getMockBuilder('\Magento\Catalog\Model\Resource\Eav\Attribute')
            ->setMethods(['getBackendType', 'getBackendTable', 'getId'])
            ->disableOriginalConstructor()
            ->getMock();
        $attribute->method('getId')
            ->willReturn($attributeId);
        $attribute->method('getBackendType')
            ->willReturn($backendType);
        $attribute->method('getBackendTable')
            ->willReturn($backendTable);
        $this->attributeCollection->expects($this->at($positionInCollection))
            ->method('getItemByColumnValue')
            ->with('attribute_code', $code)
            ->willReturn($attribute);
    }
}
