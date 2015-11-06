<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogSearch\Model\Adapter\Mysql\Plugin\Aggregation\Category;

use Magento\Catalog\Model\Layer\Resolver;
use Magento\Framework\App\Resource;
use Magento\Framework\App\ScopeResolverInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Select;
use Magento\Framework\Search\Request\BucketInterface;
use Magento\Framework\Search\Request\Dimension;

class DataProvider
{
    /**
     * @var Resource
     */
    private $resource;

    /**
     * @var ScopeResolverInterface
     */
    private $scopeResolver;

    /**
     * Category factory
     *
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @param Resource $resource
     * @param ScopeResolverInterface $scopeResolver
     * @param Resolver $layerResolver
     */
    public function __construct(
        Resource $resource,
        ScopeResolverInterface $scopeResolver,
        Resolver $layerResolver
    ) {
        $this->resource = $resource;
        $this->scopeResolver = $scopeResolver;
        $this->layer = $layerResolver->get();
    }

    /**
     * @param \Magento\CatalogSearch\Model\Adapter\Mysql\Aggregation\DataProvider $subject
     * @param callable|\Closure $proceed
     * @param BucketInterface $bucket
     * @param Dimension[] $dimensions
     *
     * @param Table $entityIdsTable
     * @return Select
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundGetDataSet(
        \Magento\CatalogSearch\Model\Adapter\Mysql\Aggregation\DataProvider $subject,
        \Closure $proceed,
        BucketInterface $bucket,
        array $dimensions,
        Table $entityIdsTable
    ) {
        if ($bucket->getField() == 'category_ids') {
            $currentScope = $dimensions['scope']->getValue();
            $currentScopeId = $this->scopeResolver->getScope($currentScope)->getId();
            $currentCategory = $this->layer->getCurrentCategory();

            $derivedTable = $this->getSelect();
            $derivedTable->from(
                ['main_table' => $this->resource->getTableName('catalog_category_product_index')],
                [
                    'entity_id' => 'product_id',
                    'value' => 'category_id',
                ]
            )->where('main_table.store_id = ?', $currentScopeId);
            $derivedTable->joinInner(
                ['entities' => $entityIdsTable->getName()],
                'main_table.product_id  = entities.entity_id',
                []
            );

            if (!empty($currentCategory)) {
                $derivedTable->join(
                    ['category' => $this->resource->getTableName('catalog_category_entity')],
                    'main_table.category_id = category.entity_id',
                    []
                )->where('`category`.`path` LIKE ?', $currentCategory->getPath() . '%')
                    ->where('`category`.`level` > ?', $currentCategory->getLevel());
            }
            $select = $this->getSelect();
            $select->from(['main_table' => $derivedTable]);
            return $select;
        }
        return $proceed($bucket, $dimensions, $entityIdsTable);
    }

    /**
     * @return Select
     */
    private function getSelect()
    {
        return $this->getConnection()->select();
    }

    /**
     * @return AdapterInterface
     */
    private function getConnection()
    {
        return $this->resource->getConnection(Resource::DEFAULT_READ_RESOURCE);
    }
}
