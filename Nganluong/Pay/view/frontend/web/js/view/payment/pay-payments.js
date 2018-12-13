/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* @api */
define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
], function (Component, rendererList) {
    'use strict';
    rendererList.push(
        {
            type: 'nganluong',
            component: 'Nganluong_Pay/js/view/payment/method-renderer/pay-method'
        }
    );
    return Component.extend({});
});