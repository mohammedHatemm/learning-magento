/**
 * RequireJS configuration for Bosta module
 */
var config = {
    map: {
        '*': {
            bostaCities: 'Elsherif_Bosta/js/bosta-cities',
            bostaCheckoutInit: 'Elsherif_Bosta/js/checkout-bosta-init'
        }
    },
    config: {
        mixins: {
            'Magento_Ui/js/form/element/abstract': {
                'Elsherif_Bosta/js/view/form/element/city-mixin': true
            }
        }
    }
};
