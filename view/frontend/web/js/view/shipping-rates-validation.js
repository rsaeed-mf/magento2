define([
    'uiComponent',
    'Magento_Checkout/js/model/shipping-rates-validator',
    'Magento_Checkout/js/model/shipping-rates-validation-rules',
    'MyFatoorah_Gateway/js/model/shipping-rates-validator',
    'MyFatoorah_Gateway/js/model/shipping-rates-validation-rules'
], function (
    Component,
    defaultShippingRatesValidator,
    defaultShippingRatesValidationRules,
    myfatoorahShippingRatesValidator,
    myfatoorahShippingRatesValidationRules
) {
    'use strict';

    defaultShippingRatesValidator.registerValidator('myfatoorah_shipping', myfatoorahShippingRatesValidator);
    defaultShippingRatesValidationRules.registerRules('myfatoorah_shipping', myfatoorahShippingRatesValidationRules);

    return Component;
});
