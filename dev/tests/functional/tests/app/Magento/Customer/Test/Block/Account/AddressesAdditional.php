<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Customer\Test\Block\Account;

use Magento\Customer\Test\Fixture\Address;
use Magento\Mtf\Block\Block;
use Magento\Mtf\Client\Locator;

/**
 * Class AddressesAdditional
 * Additional Addresses block
 */
class AddressesAdditional extends Block
{
    /**
     * Selector for address block
     *
     * @var string
     */
    protected $addressSelector = '//li[address[contains(.,"%s")]]';

    /**
     * Selector for delete link
     *
     * @var string
     */
    protected $deleteAddressLink = "[role='delete-address']";

    /**
     * Content of additional address block
     *
     * @var string
     */
    protected $additionalAddressContent = '.block-content';

    /**
     * Delete Additional Address
     *
     * @param Address $address
     * @return void
     */
    public function deleteAdditionalAddress(Address $address)
    {
        $this->_rootElement->find(sprintf($this->addressSelector, $address->getStreet()), Locator::SELECTOR_XPATH)
            ->find($this->deleteAddressLink)->click();
        $this->browser->acceptAlert();
    }

    /**
     * Get block text
     *
     * @return string
     */
    public function getBlockText()
    {
        return $this->_rootElement->find($this->additionalAddressContent)->getText();
    }
}
