<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogSearch\Model\Search;

use Magento\Catalog\Model\Resource\Product\Attribute\CollectionFactory;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\App\Resource as AppResource;
use Magento\Framework\App\Resource;
use Magento\Framework\Search\Request\FilterInterface;
use Magento\Framework\Search\Request\Filter\Bool;
use Magento\Framework\Search\Request\Query\Filter;
use Magento\Framework\Search\Request\QueryInterface as RequestQueryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Search\RequestInterface;

class TableMapper
{
    /**
     * @var Resource
     */
    private $resource;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Catalog\Model\Resource\Product\Attribute\Collection
     */
    private $attributeCollection;

    /**
     * @param AppResource $resource
     * @param StoreManagerInterface $storeManager
     * @param CollectionFactory $attributeCollectionFactory
     */
    public function __construct(
        AppResource $resource,
        StoreManagerInterface $storeManager,
        CollectionFactory $attributeCollectionFactory
    ) {
        $this->resource = $resource;
        $this->storeManager = $storeManager;
        $this->attributeCollection = $attributeCollectionFactory->create();
    }

    /**
     * @param Select $select
     * @param RequestInterface $request
     * @return Select
     */
    public function addTables(Select $select, RequestInterface $request)
    {
        $mappedTables = [];
        $filters = $this->getFilters($request->getQuery());
        foreach ($filters as $filter) {
            list($alias, $table, $mapOn, $mappedFields) = $this->getMappingData($filter);
            if (!array_key_exists($alias, $mappedTables)) {
                $select->joinLeft(
                    [$alias => $table],
                    $mapOn,
                    $mappedFields
                );
                $mappedTables[$alias] = $table;
            }
        }
        return $select;
    }

    /**
     * @param FilterInterface $filter
     * @return string
     */
    public function getMappingAlias(FilterInterface $filter)
    {
        list($alias) = $this->getMappingData($filter);
        return $alias;
    }

    /**
     * Returns mapping data for field in format: [
     *  'table_alias',
     *  'table',
     *  'join_condition',
     *  ['fields']
     * ]
     * @param FilterInterface $filter
     * @return array
     */
    private function getMappingData(FilterInterface $filter)
    {
        $alias = null;
        $table = null;
        $mapOn = null;
        $mappedFields = null;
        $field = $filter->getField();
        $fieldToTableMap = $this->getFieldToTableMap($field);
        if ($fieldToTableMap) {
            list($alias, $table, $mapOn, $mappedFields) = $fieldToTableMap;
            $table = $this->resource->getTableName($table);
        } elseif ($filter->getType() === FilterInterface::TYPE_TERM) {
            $attribute = $this->getAttributeByCode($field);
            if ($attribute) {
                $table = $this->resource->getTableName('catalog_product_index_eav');
                $alias = $field . '_filter';
                $mapOn = sprintf(
                    'search_index.entity_id = %1$s.entity_id AND %1$s.attribute_id = %2$d AND %1$s.store_id = %3$d',
                    $alias,
                    $attribute->getId(),
                    $this->getStoreId()
                );
                $mappedFields = [];
            }
        } else {
            $attribute = $this->getAttributeByCode($field);
            if ($attribute && $attribute->getBackendType() === AbstractAttribute::TYPE_STATIC) {
                $table = $attribute->getBackendTable();
                $alias = $this->getTableAlias($table);
                $mapOn = 'search_index.entity_id = ' . $alias . '.entity_id';
                $mappedFields = null;
            }
        }

        if (!$alias && $table) {
            $alias = $this->getTableAlias($table);
        }

        return [$alias, $table, $mapOn, $mappedFields];
    }

    /**
     * @param RequestQueryInterface $query
     * @return FilterInterface[]
     */
    private function getFilters($query)
    {
        $filters = [];
        switch ($query->getType()) {
            case RequestQueryInterface::TYPE_BOOL:
                /** @var \Magento\Framework\Search\Request\Query\Bool $query */
                foreach ($query->getMust() as $subQuery) {
                    $filters = array_merge($filters, $this->getFilters($subQuery));
                }
                foreach ($query->getShould() as $subQuery) {
                    $filters = array_merge($filters, $this->getFilters($subQuery));
                }
                foreach ($query->getMustNot() as $subQuery) {
                    $filters = array_merge($filters, $this->getFilters($subQuery));
                }
                break;
            case RequestQueryInterface::TYPE_FILTER:
                /** @var Filter $query */
                $filter = $query->getReference();
                if (FilterInterface::TYPE_BOOL === $filter->getType()) {
                    $filters = array_merge($filters, $this->getFiltersFromBoolFilter($filter));
                } else {
                    $filters[] = $filter;
                }
                break;
            default:
                break;
        }
        return $filters;
    }

    /**
     * @param Bool $boolExpression
     * @return FilterInterface[]
     */
    private function getFiltersFromBoolFilter(Bool $boolExpression)
    {
        $filters = [];
        /** @var Bool $filter */
        foreach ($boolExpression->getMust() as $filter) {
            if ($filter->getType() === FilterInterface::TYPE_BOOL) {
                $filters = array_merge($filters, $this->getFiltersFromBoolFilter($filter));
            } else {
                $filters[] = $filter;
            }
        }
        foreach ($boolExpression->getShould() as $filter) {
            if ($filter->getType() === FilterInterface::TYPE_BOOL) {
                $filters = array_merge($filters, $this->getFiltersFromBoolFilter($filter));
            } else {
                $filters[] = $filter;
            }
        }
        foreach ($boolExpression->getMustNot() as $filter) {
            if ($filter->getType() === FilterInterface::TYPE_BOOL) {
                $filters = array_merge($filters, $this->getFiltersFromBoolFilter($filter));
            } else {
                $filters[] = $filter;
            }
        }
        return $filters;
    }

    /**
     * @return int
     */
    private function getWebsiteId()
    {
        return $this->storeManager->getWebsite()->getId();
    }

    /**
     * @return int
     */
    private function getStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    /**
     * @param string $field
     * @return array|null
     */
    private function getFieldToTableMap($field)
    {
        $fieldToTableMap = [
            'price' => [
                'price_index',
                'catalog_product_index_price',
                $this->resource->getConnection(Resource::DEFAULT_READ_RESOURCE)->quoteInto(
                    'search_index.entity_id = price_index.entity_id AND price_index.website_id = ?',
                    $this->getWebsiteId()
                ),
                []
            ],
            'category_ids' => [
                'category_ids_index',
                'catalog_category_product_index',
                'search_index.entity_id = category_ids_index.product_id',
                []
            ]
        ];
        return array_key_exists($field, $fieldToTableMap) ? $fieldToTableMap[$field] : null;
    }

    /**
     * @param string $table
     * @return string
     */
    private function getTableAlias($table)
    {
        return md5($table);
    }

    /**
     * @param string $field
     * @return \Magento\Catalog\Model\Resource\Eav\Attribute
     */
    private function getAttributeByCode($field)
    {
        $attribute = $this->attributeCollection->getItemByColumnValue('attribute_code', $field);
        return $attribute;
    }
}
