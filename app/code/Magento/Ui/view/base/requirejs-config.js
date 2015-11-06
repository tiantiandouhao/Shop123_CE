/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

var config = {
    paths: {
        'ui/template': 'Magento_Ui/templates',
        'i18n': 'Magento_Ui/js/lib/i18n'
    },
    map: {
        '*': {
            uiComponent: 'Magento_Ui/js/lib/component/main',
            uiRegistry: 'Magento_Ui/js/lib/registry/registry',
            uiLayout: 'Magento_Ui/js/core/renderer/layout'
        }
    }
};