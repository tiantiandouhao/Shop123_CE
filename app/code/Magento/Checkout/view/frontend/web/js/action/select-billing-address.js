/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*global define*/
define(
    [
        'jquery',
        '../model/quote'
    ],
    function($, quote) {
        "use strict";
        return function(billingAddress) {
            var address = null;
            if (billingAddress.getCacheKey() == quote.shippingAddress().getCacheKey()) {
                address = $.extend({}, billingAddress);
                address.save_in_address_book = false;
            } else {
                address = billingAddress;
            }
            quote.billingAddress(address);
        };
    }
);
