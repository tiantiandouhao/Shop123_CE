<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cms\Controller\Adminhtml\Page;

use Magento\Cms\Controller\Adminhtml\AbstractMassStatus;

/**
 * Class MassDisable
 */
class MassDisable extends AbstractMassStatus
{
    /**
     * Field id
     */
    const ID_FIELD = 'page_id';

    /**
     * Resource collection
     *
     * @var string
     */
    protected $collection = 'Magento\Cms\Model\Resource\Page\Collection';

    /**
     * Page model
     *
     * @var string
     */
    protected $model = 'Magento\Cms\Model\Page';

    /**
     * Page disable status
     *
     * @var boolean
     */
    protected $status = false;
}
