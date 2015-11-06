<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Customer Session Event Observer
 */
namespace Magento\Weee\Model\Observer;

class Session
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Weee\Helper\Data
     */
    protected $weeeHelper;

    /**
     * Module manager
     *
     * @var \Magento\Framework\Module\Manager
     */
    private $moduleManager;

    /**
     * Cache config
     *
     * @var \Magento\PageCache\Model\Config
     */
    private $cacheConfig;

    /**
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Weee\Helper\Data $weeeHelper
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\PageCache\Model\Config $cacheConfig
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Weee\Helper\Data $weeeHelper,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\PageCache\Model\Config $cacheConfig
    ) {
        $this->customerSession = $customerSession;
        $this->weeeHelper = $weeeHelper;
        $this->moduleManager = $moduleManager;
        $this->cacheConfig = $cacheConfig;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function customerLoggedIn(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->moduleManager->isEnabled('Magento_PageCache') && $this->cacheConfig->isEnabled() &&
            $this->weeeHelper->isEnabled()) {
            /** @var \Magento\Customer\Model\Data\Customer $customer */
            $customer = $observer->getData('customer');

            /** @var \Magento\Customer\Api\Data\AddressInterface[] $addresses */
            $addresses = $customer->getAddresses();
            if (isset($addresses)) {
                $defaultShippingFound = false;
                $defaultBillingFound = false;
                foreach ($addresses as $address) {
                    if ($address->isDefaultBilling()) {
                        $defaultBillingFound = true;
                        $this->customerSession->setDefaultTaxBillingAddress(
                            [
                                'country_id' => $address->getCountryId(),
                                'region_id'  => $address->getRegion() ? $address->getRegion()->getRegionId() : null,
                                'postcode'   => $address->getPostcode(),
                            ]
                        );
                    }
                    if ($address->isDefaultShipping()) {
                        $defaultShippingFound = true;
                        $this->customerSession->setDefaultTaxShippingAddress(
                            [
                                'country_id' => $address->getCountryId(),
                                'region_id'  => $address->getRegion() ? $address->getRegion()->getRegionId() : null,
                                'postcode'   => $address->getPostcode(),
                            ]
                        );
                    }
                    if ($defaultShippingFound && $defaultBillingFound) {
                        break;
                    }
                }
            }
        }
    }

    /**
     * Address after save event handler
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function afterAddressSave($observer)
    {
        if ($this->moduleManager->isEnabled('Magento_PageCache') && $this->cacheConfig->isEnabled() &&
            $this->weeeHelper->isEnabled()) {
            /** @var $customerAddress Address */
            $address = $observer->getCustomerAddress();

            // Check if the address is either the default billing, shipping, or both
            if ($address->getIsPrimaryBilling() || $address->getIsDefaultBilling()) {
                $this->customerSession->setDefaultTaxBillingAddress(
                    [
                        'country_id' => $address->getCountryId(),
                        'region_id'  => $address->getRegion() ? $address->getRegionId() : null,
                        'postcode'   => $address->getPostcode(),
                    ]
                );
            }

            if ($address->getIsPrimaryShipping() || $address->getIsDefaultShipping()) {
                $this->customerSession->setDefaultTaxShippingAddress(
                    [
                        'country_id' => $address->getCountryId(),
                        'region_id'  => $address->getRegion() ? $address->getRegionId() : null,
                        'postcode'   => $address->getPostcode(),
                    ]
                );
            }
        }
    }
}
