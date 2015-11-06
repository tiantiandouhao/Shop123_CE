<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Framework\Api;

use Magento\Framework\Api\Search\FilterGroupBuilder;

/**
 * Builder for SearchCriteria Service Data Object
 */
class SearchCriteriaBuilder extends AbstractSimpleObjectBuilder
{
    /**
     * @var FilterGroupBuilder
     */
    protected $_filterGroupBuilder;

    /**
     * @param ObjectFactory $objectFactory
     * @param FilterGroupBuilder $filterGroupBuilder
     */
    public function __construct(
        ObjectFactory $objectFactory,
        FilterGroupBuilder $filterGroupBuilder
    ) {
        parent::__construct(
            $objectFactory
        );
        $this->_filterGroupBuilder = $filterGroupBuilder;
    }

    /**
     * Builds the SearchCriteria Data Object
     *
     * @return SearchCriteria
     */
    public function create()
    {
        //Initialize with empty array if not set
        if (empty($this->data[SearchCriteria::FILTER_GROUPS])) {
            $this->_set(SearchCriteria::FILTER_GROUPS, []);
        }
        return parent::create();
    }

    /**
     * Create a filter group based on the filter array provided and add to the filter groups
     *
     * @param \Magento\Framework\Api\Filter[] $filter
     * @return $this
     */
    public function addFilters(array $filter)
    {
        $this->data[SearchCriteria::FILTER_GROUPS][] = $this->_filterGroupBuilder->setFilters($filter)->create();
        return $this;
    }

    /**
     * Set filter groups
     *
     * @param \Magento\Framework\Api\Search\FilterGroup[] $filterGroups
     * @return $this
     */
    public function setFilterGroups(array $filterGroups)
    {
        return $this->_set(SearchCriteria::FILTER_GROUPS, $filterGroups);
    }

    /**
     * Add sort order
     *
     * @param SortOrder $sortOrder
     * @return $this
     */
    public function addSortOrder($sortOrder)
    {
        if (!isset($this->data[SearchCriteria::SORT_ORDERS])) {
            $this->data[SearchCriteria::SORT_ORDERS] = [];
        }
        $this->data[SearchCriteria::SORT_ORDERS][] = $sortOrder;
        return $this;
    }

    /**
     * Set sort orders
     *
     * @param SortOrder[] $sortOrders
     * @return $this
     */
    public function setSortOrders(array $sortOrders)
    {
        return $this->_set(SearchCriteria::SORT_ORDERS, $sortOrders);
    }

    /**
     * Set page size
     *
     * @param int $pageSize
     * @return $this
     */
    public function setPageSize($pageSize)
    {
        return $this->_set(SearchCriteria::PAGE_SIZE, $pageSize);
    }

    /**
     * Set current page
     *
     * @param int $currentPage
     * @return $this
     */
    public function setCurrentPage($currentPage)
    {
        return $this->_set(SearchCriteria::CURRENT_PAGE, $currentPage);
    }
}
