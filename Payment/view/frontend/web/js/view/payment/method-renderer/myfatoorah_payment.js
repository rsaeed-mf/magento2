/*browser:true*/
/*global define*/
define(
        [
            'jquery',
            'Magento_Checkout/js/view/payment/default',
            'mage/url',
            'mfSessionFile' // here the session.js file is mapped
        ],
        function (
                $,
                Component,
                url
                ) {
            'use strict';

            var self;

            var mfData = 'pm=myfatoorah';
            var paymentMethods = window.checkoutConfig.payment.myfatoorah_payment.paymentMethods;
            var listOptions = window.checkoutConfig.payment.myfatoorah_payment.listOptions;
            var mfLang = window.checkoutConfig.payment.myfatoorah_payment.lang;

            return Component.extend({
                redirectAfterPlaceOrder: false,

                defaults: {
                    template: 'MyFatoorah_Payment/payment/form'
                },

                initialize: function () {
                    this._super();
                    self = this;
                },
                initObservable: function () {
                    this._super()
                            .observe([
                                'transactionResult',
                                'gateways'
                            ]);

                    return this;

                },
                getCode: function () {
                    return 'myfatoorah_payment';
                },
                getData: function () {
                    return {
                        'method': this.item.method,
                        'additional_data': {
                            'transaction_result': this.transactionResult(),
                            'gateways': this.gateways()
                        }
                    };
                },
                validate: function () {
                    return true;
                },
                afterPlaceOrder: function () {
                    window.location.replace(url.build('myfatoorah_payment/checkout/index?' + mfData));
                },

                placeOrderCard: function (paymentMethodId) {
                    $('body').loader('show');
                    mfData = 'pm=' + paymentMethodId;
                    self.placeOrder();
                    return;
                },
                placeOrderForm: function () {
                    $('body').loader('show');
                    if (listOptions === 'myfatoorah' || paymentMethods.all.length === 0) {
                        mfData = 'pm=myfatoorah';
                        self.placeOrder();
                        return;
                    }

                    if (paymentMethods.cards.length === 1 && paymentMethods.form.length === 0) {
                        mfData = 'pm=' + paymentMethods['cards'][0]['PaymentMethodId'];
                        self.placeOrder();
                        return;
                    }

                    myFatoorah.submit()
                            .then(function (response) {// On success
                                mfData = 'sid=' + response.SessionId;
                                self.placeOrder();
                            }, function (error) { // In case of errors
                                $('body').loader('hide');
                                self.messageContainer.addErrorMessage({
                                    message: error
                                });
                            });
                },

                getTitle: function () {
                    return window.checkoutConfig.payment.myfatoorah_payment.title;
                },

                paymentMethods: paymentMethods,

                isSectionVisible: function (section) {
                    return (paymentMethods[section].length > 0);
                },
                isContainerVisible: function () {
                    if (listOptions === 'myfatoorah' || paymentMethods.all.length === 0) {
                        return false;
                    }

                    if (paymentMethods.cards.length === 1 && paymentMethods.form.length === 0) {
                        return false;
                    }

                    return true;
                },

                getCardTitle: function (mfCard) {
                    return (mfLang === 'ar') ? mfCard.PaymentMethodAr : mfCard.PaymentMethodEn;

                },

                getForm: function () {
                    var magConfig = window.checkoutConfig.payment.myfatoorah_payment;

                    var mfConfig = {
                        countryCode: magConfig.countryCode,
                        sessionId: magConfig.sessionId,
                        cardViewId: "mf-card-element",
                        // The following style is optional.
                        style: {
                            cardHeight: magConfig.height,
                            direction: (mfLang === 'ar') ? 'rtl' : 'ltr',
                            input: {
                                color: "black",
                                fontSize: "13px",
                                fontFamily: "sans-serif",
                                inputHeight: "32px",
                                inputMargin: "-1px",
                                borderColor: "c7c7c7",
                                borderWidth: "1px",
                                borderRadius: "0px",
                                boxShadow: "",
                                placeHolder: {
                                    holderName: $.mage.translate.add('Name On Card"'),
                                    cardNumber: $.mage.translate.add('Number'),
                                    expiryDate: $.mage.translate.add('MM / YY'),
                                    securityCode: $.mage.translate.add('CVV'),
                                }
                            },
                            label: {
                                display: false,
                                color: "black",
                                fontSize: "13px",
                                fontFamily: "sans-serif",
                                text: {
                                    holderName: "Card Holder Name",
                                    cardNumber: "Card Number",
                                    expiryDate: "ExpiryDate",
                                    securityCode: "Security Code"
                                }
                            },
                            error: {
                                borderColor: "red",
                                borderRadius: "8px",
                                boxShadow: "0px"
                            }
                        }
                    };

                    myFatoorah.init(mfConfig);
                    window.addEventListener("message", myFatoorah.recievedMessage, false);
                }

            });
        }
);
