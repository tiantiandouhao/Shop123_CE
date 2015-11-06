/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    './abstract'
], function (Abstract) {
    'use strict';

    return Abstract.extend({
        /**
         * Converts the result of parent 'getInitialValue' call to boolean
         *
         * @return {Boolean}
         */
        getInitialValue: function () {
            return !!+this._super();
        },

        /**
         * Calls 'store' method of parent, if value is defined and instance's
         *     'unique' property set to true, calls 'setUnique' method
         *
         * @param  {*} value
         * @return {Object} - reference to instance
         */
        onUpdate: function () {
            if (this.hasUnique) {
                this.setUnique();
            }

            return this._super();
        }
    });
});
