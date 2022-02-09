<?php

namespace MyFatoorah\Gateway\Plugin;

use Magento\Payment\Model\MethodList;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Framework\Module\Manager;
use Magento\Framework\App\Config\ScopeConfigInterface;

class MethodListPlugin {

    /**
     * Core store config
     *
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

//-----------------------------------------------------------------------------------------------------------------------------------------
    public function __construct(
            Manager $moduleManager,
            ScopeConfigInterface $scopeConfig) {

        $this->moduleManager = $moduleManager;
        $this->_scopeConfig  = $scopeConfig;
    }

//-----------------------------------------------------------------------------------------------------------------------------------------
    public function afterGetAvailableMethods(MethodList $subject, $availableMethods, CartInterface $quote = null) {

        $shippingMethod = $quote ? $quote->getShippingAddress()->getShippingMethod() : '';

        $mfPaymentCode = $this->getMFPaymentCode();
        if ($shippingMethod == 'myfatoorah_shipping_1' || $shippingMethod == 'myfatoorah_shipping_2') {

            foreach ($availableMethods as $key => $method) {
                if ($method->getCode() != $mfPaymentCode) {
                    unset($availableMethods[$key]);
                }
            }
        }
        return $availableMethods;
    }

//-----------------------------------------------------------------------------------------------------------------------------------------
    function getMFPaymentCode() {

//        $store = $this->getStore();
        $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

        if ($this->moduleManager->isEnabled('MyFatoorah_Gateway') && $this->_scopeConfig->getValue('payment/myfatoorah_payment/active', $scope)) {
            return 'myfatoorah_payment';
        } else if ($this->moduleManager->isEnabled('MyFatoorah_MyFatoorahPaymentGateway') && $this->_scopeConfig->getValue('payment/myfatoorah_gateway/active', $scope)) {
            return 'myfatoorah_gateway';
        } else if ($this->moduleManager->isEnabled('MyFatoorah_EmbedPay') && $this->_scopeConfig->getValue('payment/embedpay/active', $scope)) {
            return 'embedpay';
        } else {
            return false;
        }
    }

//-----------------------------------------------------------------------------------------------------------------------------------------
}
