/**
 * Initialize Bosta Cities in Checkout
 */
define([
    'jquery',
    'Elsherif_Bosta/js/bosta-cities',
    'domReady!'
], function ($, bostaCities) {
    'use strict';

    return function () {
        // Only handle customer account address book (NOT checkout)
        // Checkout is handled by city-mixin.js to properly integrate with Knockout
        if ($('.form-address-edit').length > 0) {
            bostaCities.init(
                'input[name="city"]',
                'select[name="country_id"]'
            );
        }
    };
});
