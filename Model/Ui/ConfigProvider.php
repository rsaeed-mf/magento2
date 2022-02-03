<?php

namespace MyFatoorah\Gateway\Model\Ui;

use MyFatoorah\Gateway\Gateway\Config\Config;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Locale\Resolver;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Checkout\Model\Cart;

/**
 * Class ConfigProvider
 */
final class ConfigProvider implements ConfigProviderInterface {

    /**
     * @var Config
     */
    private $_gatewayConfig;

    /**
     * @var Resolver
     */
    private $localeResolver;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var Cart
     */
    private $cart;

//---------------------------------------------------------------------------------------------------------------------------------------------------
    public function __construct(
            Config $gatewayConfig,
            Resolver $localeResolver,
            CustomerSession $customerSession,
            Cart $cart
    ) {
        $this->_gatewayConfig  = $gatewayConfig;
        $this->localeResolver  = $localeResolver;
        $this->customerSession = $customerSession;
        $this->cart            = $cart;
    }

//---------------------------------------------------------------------------------------------------------------------------------------------------
    public function getConfig() {



        $config = [
            'title'       => $this->_gatewayConfig->getTitle(),
            'listOptions' => $this->_gatewayConfig->getKeyGateways(),
        ];

        if ($config['listOptions'] == 'multigateways') {
            try {
                $config = $this->fillMultigatewaysData($config);
            } catch (\Exception $ex) {
                $config['mfError'] = $ex->getMessage();
            }
        }

        return [
            'payment' => [
                Config::CODE => $config
            ]
        ];
    }

//---------------------------------------------------------------------------------------------------------------------------------------------------
    private function fillMultigatewaysData($config) {

        $config['lang'] = $this->getCurrentLocale();

        $mfObj = $this->_gatewayConfig->getMyfatoorahObject();

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->cart->getQuote();

        $config['paymentMethods'] = $mfObj->getPaymentMethodsForDisplay($quote->getBaseGrandTotal(), $quote->getBaseCurrencyCode());

        $all = $config['paymentMethods']['all'];
        if (count($all) == 1) {
            $config['title'] = ($config['lang'] == 'ar') ? $all[0]->PaymentMethodAr : $all[0]->PaymentMethodEn;
        }

        //draw form section
        if (count($config['paymentMethods']['form']) == 0) {
            return $config;
        }

        $customerId = $this->customerSession->getCustomer()->getId();

        $config['height'] = '130';
        $userDefinedField = '';
        if ($this->_gatewayConfig->getSaveCard() && $customerId) {
            $config['height'] = '180';
            $userDefinedField = 'CK-' . $customerId;
        }
        $initSession           = $mfObj->getEmbeddedSession($userDefinedField);
        $config['countryCode'] = $initSession->CountryCode;
        $config['sessionId']   = $initSession->SessionId;

        return $config;
    }

//---------------------------------------------------------------------------------------------------------------------------------------------------
    private function getCurrentLocale() {
        $currentLocaleCode = $this->localeResolver->getLocale(); // fr_CA
        $languageCode      = strstr($currentLocaleCode, '_', true);
        return $languageCode;
    }

//---------------------------------------------------------------------------------------------------------------------------------------------------

    /** @var \Magento\Sales\Model\Order $order */
    function getInvoiceItems() {

        $amount          = 0;
        $mfShipping      = 0;
        $invoiceItemsArr = [];

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->cart->getQuote();

        $shippingMethod = $quote->getShippingMethod();
        $isShipping     = null;
        if (($shippingMethod == 'myfatoorah_shipping_1') || ($shippingMethod == 'myfatoorah_shippingDHL_myfatoorah_shippingDHL')) {
            $isShipping = 1;
        } else if (($shippingMethod == 'myfatoorah_shipping_2') || ($shippingMethod == 'myfatoorah_shippingAramex_myfatoorah_shippingAramex')) {
            $isShipping = 2;
        }

        $store        = $this->objectManager->create('Magento\Store\Model\StoreManagerInterface')->getStore();
        $currencyRate = $store->getBaseCurrency();

        /** @var \Magento\Sales\Api\Data\OrderItemInterface[]  $items */
        $items = $quote->getAllVisibleItems();
        foreach ($items as $item) {
            $itemPrice = round($item->getBasePrice() * $currencyRate, 3);
            $qty       = intval($item->getQtyOrdered());

            $invoiceItemsArr[] = [
                'ItemName'  => $item->getName(),
                'Quantity'  => $qty,
                'UnitPrice' => "$itemPrice",
            ];
            $amount            += round($itemPrice * $qty, 3);
        }


        $shipping = $quote->getBaseShippingAmount() + $quote->getBaseShippingTaxAmount();
        if (!empty($shipping)) {
            $itemPrice         = round($shipping * $currencyRate, 3);
            $invoiceItemsArr[] = ['ItemName' => 'Shipping Amount', 'Quantity' => '1', 'UnitPrice' => "$itemPrice", 'Weight' => '0', 'Width' => '0', 'Height' => '0', 'Depth' => '0'];

            if (!$isShipping) {
                $amount += $itemPrice;
            } else {
                $mfShipping = $itemPrice;
            }


            $discount = $order->getBaseDiscountAmount();
            if ($discount != 0) {
                $itemPrice         = round($discount * $currencyRate, 3);
                $invoiceItemsArr[] = ['ItemName' => 'Discount Amount', 'Quantity' => '1', 'UnitPrice' => "$itemPrice", 'Weight' => '0', 'Width' => '0', 'Height' => '0', 'Depth' => '0'];

                $amount += $itemPrice;
            }

            $tax = $order->getBaseTaxAmount();
            if (!empty($tax)) {
                $itemPrice         = round($tax * $currencyRate, 3);
                $invoiceItemsArr[] = ['ItemName' => 'Tax Amount', 'Quantity' => '1', 'UnitPrice' => "$itemPrice", 'Weight' => '0', 'Width' => '0', 'Height' => '0', 'Depth' => '0'];

                $amount += $itemPrice;
            }

            //Mageworx
            $fees = $order->getBaseMageworxFeeAmount();
            if (!empty($fees)) {
                $itemPrice         = round($fees * $currencyRate, 3);
                $invoiceItemsArr[] = array('ItemName' => 'Additional Fees', 'Quantity' => 1, 'UnitPrice' => "$itemPrice");
                $amount            += $itemPrice;
            }

            $productFees = $order->getBaseMageworxProductFeeAmount();
            if (!empty($productFees)) {
                $itemPrice         = round($productFees * $currencyRate, 3);
                $invoiceItemsArr[] = array('ItemName' => 'Additional Product Fees', 'Quantity' => 1, 'UnitPrice' => "$itemPrice");
                $amount            += $itemPrice;
            }
            return $invoiceItemsArr;
        }

//---------------------------------------------------------------------------------------------------------------------------------------------------
//---------------------------------------------------------------------------------------------------------------------------------------------------   
    }

}
