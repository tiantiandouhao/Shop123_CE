<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Mtf\Util\Generate\Repository;

use Magento\Framework\Model\Resource\Db\Collection\AbstractCollection;

/**
 * Class CollectionProvider
 *
 */
class TableCollection extends AbstractCollection
{
    /**
     * @var array
     */
    protected $fixture;

    /**
     * @constructor
     * @param \Magento\Framework\Data\Collection\EntityFactory $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param null $connection
     * @param \Magento\Framework\Model\Resource\Db\AbstractDb $resource
     * @param array $fixture
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactory $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        $connection = null,
        \Magento\Framework\Model\Resource\Db\AbstractDb $resource = null,
        array $fixture = []
    ) {
        $this->setModel('Magento\Framework\Object');
        $this->setResourceModel('Magento\Mtf\Util\Generate\Repository\Resource');

        $resource = $this->getResource();
        $resource->setFixture($fixture);

        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
    }

    /**
     * Get resource instance
     *
     * @return \Magento\Mtf\Util\Generate\Repository\Resource
     */
    public function getResource()
    {
        return parent::getResource();
    }
}
