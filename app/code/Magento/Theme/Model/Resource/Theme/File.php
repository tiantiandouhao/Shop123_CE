<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Theme\Model\Resource\Theme;

/**
 * Theme files resource model
 */
class File extends \Magento\Framework\Model\Resource\Db\AbstractDb
{
    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('theme_file', 'theme_files_id');
    }
}
