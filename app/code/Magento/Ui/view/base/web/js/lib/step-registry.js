/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    "uiComponent"
], function (Component) {
    "use strict";

    return Component.extend({
        initialize: function () {
            this._super();
            this.steps = [];
        },
        initElement: function (step) {
            this.steps.push(step);
        }
    });
});
