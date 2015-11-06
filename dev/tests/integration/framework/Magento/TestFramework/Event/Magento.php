<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\TestFramework\Event;

/**
 * Observer of Magento events triggered using \Magento\TestFramework\EventManager::dispatch()
 */
class Magento
{
    /**
     * Used when Magento framework instantiates the class on its own and passes nothing to the constructor
     *
     * @var \Magento\TestFramework\EventManager
     */
    protected static $_defaultEventManager;

    /**
     * @var \Magento\TestFramework\EventManager
     */
    protected $_eventManager;

    /**
     * Assign default event manager instance
     *
     * @param \Magento\TestFramework\EventManager $eventManager
     */
    public static function setDefaultEventManager(\Magento\TestFramework\EventManager $eventManager = null)
    {
        self::$_defaultEventManager = $eventManager;
    }

    /**
     * Constructor
     *
     * @param \Magento\TestFramework\EventManager $eventManager
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct($eventManager = null)
    {
        $this->_eventManager = $eventManager ?: self::$_defaultEventManager;
        if (!$this->_eventManager instanceof \Magento\TestFramework\EventManager) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Instance of the "Magento\TestFramework\EventManager" is expected.')
            );
        }
    }

    /**
     * Handler for 'core_app_init_current_store_after' event, that converts it into 'initStoreAfter'
     */
    public function initStoreAfter()
    {
        $this->_eventManager->fireEvent('initStoreAfter');
    }
}
