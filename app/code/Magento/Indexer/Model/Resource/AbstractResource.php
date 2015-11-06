<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Abstract resource model. Can be used as base for indexer resources
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\Indexer\Model\Resource;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;

abstract class AbstractResource extends \Magento\Framework\Model\Resource\Db\AbstractDb
{
    /**
     * Constructor
     *
     * @var \Magento\Indexer\Model\Indexer\Table\StrategyInterface
     */
    protected $tableStrategy;

    /**
     * Class constructor
     *
     * @param \Magento\Framework\Model\Resource\Db\Context $context
     * @param \Magento\Indexer\Model\Indexer\Table\StrategyInterface $tableStrategy
     * @param null $resourcePrefix
     */
    public function __construct(
        \Magento\Framework\Model\Resource\Db\Context $context,
        \Magento\Indexer\Model\Indexer\Table\StrategyInterface $tableStrategy,
        $resourcePrefix = null
    ) {
        $this->tableStrategy = $tableStrategy;
        parent::__construct($context, $resourcePrefix);
    }

    /**
     * Reindex all
     *
     * @return $this
     */
    public function reindexAll()
    {
        $this->tableStrategy->setUseIdxTable(true);
        return $this;
    }

    /**
     * Get DB adapter for index data processing
     *
     * @return AdapterInterface
     */
    protected function _getIndexAdapter()
    {
        return $this->_getWriteAdapter();
    }

    /**
     * Get index table name with additional suffix
     *
     * @param string $table
     * @return string
     */
    public function getIdxTable($table = null)
    {
        if ($table) {
            return $this->tableStrategy->prepareTableName($table);
        }
        return $this->tableStrategy->prepareTableName($this->getMainTable());
    }

    /**
     * Synchronize data between index storage and original storage
     *
     * @return $this
     */
    public function syncData()
    {
        $this->beginTransaction();
        try {
            /**
             * Can't use truncate because of transaction
             */
            $this->_getWriteAdapter()->delete($this->getMainTable());
            $this->insertFromTable($this->getIdxTable(), $this->getMainTable(), false);
            $this->commit();
        } catch (\Exception $e) {
            $this->rollBack();
            throw $e;
        }
        return $this;
    }

    /**
     * Copy data from source table of read adapter to destination table of index adapter
     *
     * @param string $sourceTable
     * @param string $destTable
     * @param bool $readToIndex data migration direction (true - read=>index, false - index=>read)
     * @return $this
     */
    public function insertFromTable($sourceTable, $destTable, $readToIndex = true)
    {
        if ($readToIndex) {
            $sourceColumns = array_keys($this->_getWriteAdapter()->describeTable($sourceTable));
            $targetColumns = array_keys($this->_getWriteAdapter()->describeTable($destTable));
        } else {
            $sourceColumns = array_keys($this->_getIndexAdapter()->describeTable($sourceTable));
            $targetColumns = array_keys($this->_getWriteAdapter()->describeTable($destTable));
        }
        $select = $this->_getIndexAdapter()->select()->from($sourceTable, $sourceColumns);

        $this->insertFromSelect($select, $destTable, $targetColumns, $readToIndex);
        return $this;
    }

    /**
     * Insert data from select statement of read adapter to
     * destination table related with index adapter
     *
     * @param Select $select
     * @param string $destTable
     * @param array $columns
     * @param bool $readToIndex data migration direction (true - read=>index, false - index=>read)
     * @return $this
     */
    public function insertFromSelect($select, $destTable, array $columns, $readToIndex = true)
    {
        if ($readToIndex) {
            $from = $this->_getWriteAdapter();
            $to = $this->_getIndexAdapter();
        } else {
            $from = $this->_getIndexAdapter();
            $to = $this->_getWriteAdapter();
        }

        if ($from === $to) {
            $query = $select->insertFromSelect($destTable, $columns);
            $to->query($query);
        } else {
            $stmt = $from->query($select);
            $data = [];
            $counter = 0;
            while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
                $data[] = $row;
                $counter++;
                if ($counter > 2000) {
                    $to->insertArray($destTable, $columns, $data);
                    $data = [];
                    $counter = 0;
                }
            }
            if (!empty($data)) {
                $to->insertArray($destTable, $columns, $data);
            }
        }

        return $this;
    }

    /**
     * Clean up temporary index table
     *
     * @return void
     */
    public function clearTemporaryIndexTable()
    {
        $this->_getWriteAdapter()->delete($this->getIdxTable());
    }
}
