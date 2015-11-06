/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/** Loads all available knockout bindings, sets custom template engine, initializes knockout on page */
define([
    'ko',
    './template/engine',
    './bind/scope',
    './bind/datepicker',
    './bind/stop_propagation',
    './bind/outer_click',
    './bind/keyboard',
    './bind/optgroup',
    './bind/fadeVisible',
    './bind/mage-init',
    './bind/after-render',
    './extender/observable_array'
], function (ko, templateEngine) {
    'use strict';

    ko.setTemplateEngine(templateEngine);
    ko.applyBindings();
});
