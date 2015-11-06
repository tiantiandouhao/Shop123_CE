<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Framework\Search\Adapter\Mysql;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Select;

class TemporaryStorage
{
    const TEMPORARY_TABLE_PREFIX = 'search_tmp_';

    const FIELD_ENTITY_ID = 'entity_id';
    const FIELD_SCORE = 'score';

    /**
     * @var \Magento\Framework\App\Resource
     */
    private $resource;

    /**
     * @param \Magento\Framework\App\Resource $resource
     */
    public function __construct(\Magento\Framework\App\Resource $resource)
    {
        $this->resource = $resource;
    }

    /**
     * @param \ArrayIterator|\Magento\Framework\Search\Document[] $documents
     * @return Table
     */
    public function storeDocuments($documents)
    {
        $data = [];
        foreach ($documents as $document) {
            $data[] = [
                $document->getId(),
                $document->getField('score')->getValue(),
            ];
        }

        $table = $this->createTemporaryTable();
        if (!empty($data)) {
            $this->getConnection()->insertArray(
                $table->getName(),
                [
                    self::FIELD_ENTITY_ID,
                    self::FIELD_SCORE,
                ],
                $data
            );
        }
        return $table;
    }

    /**
     * @param Select $select
     * @return Table
     * @throws \Zend_Db_Exception
     */
    public function storeDocumentsFromSelect(Select $select)
    {
        $table = $this->createTemporaryTable();
        $this->getConnection()->query($this->getConnection()->insertFromSelect($select, $table->getName()));
        return $table;
    }

    /**
     * @return false|AdapterInterface
     */
    private function getConnection()
    {
        return $this->resource->getConnection(\Magento\Framework\App\Resource::DEFAULT_READ_RESOURCE);
    }

    /**
     * @return Table
     * @throws \Zend_Db_Exception
     */
    private function createTemporaryTable()
    {
        $connection = $this->getConnection();
        $table = $connection->newTable($this->resource->getTableName(self::TEMPORARY_TABLE_PREFIX . time()));
        $connection->dropTable($table->getName());
        $table->addColumn(
            self::FIELD_ENTITY_ID,
            Table::TYPE_INTEGER,
            10,
            ['unsigned' => true, 'nullable' => false, 'primary' => true],
            'Entity ID'
        );
        $table->addColumn(
            self::FIELD_SCORE,
            Table::TYPE_DECIMAL,
            [32, 16],
            ['unsigned' => true, 'nullable' => false],
            'Score'
        );
        $table->setOption('type', 'memory');
        $connection->createTemporaryTable($table);
        return $table;
    }
}
