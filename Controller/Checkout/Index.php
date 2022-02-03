<?php

namespace MyFatoorah\Gateway\Controller\Checkout;

use Magento\Sales\Model\Order;

/**
 * @package MyFatoorah\Gateway\Controller\Checkout
 */
class Index extends AbstractAction {

    public $orderId = null;

//---------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * @return void
     */
    public function execute() {

        try {
            $order = $this->getOrder();

            $this->order   = $order;
            $this->orderId = $order->getRealOrderId();

            if ($order->getState() === Order::STATE_CANCELED) {
                $errorMessage = $this->getCheckoutSession()->getMyFatoorahErrorMessage(); //set in InitializationRequest
                if ($errorMessage) {
                    $this->getMessageManager()->addWarningMessage($errorMessage);
                    $errorMessage = $this->getCheckoutSession()->unsMyFatoorahErrorMessage();
                }
                $this->getLogger()->addNotice('Order in state: ' . $order->getState());
                $this->getCheckoutHelper()->restoreQuote(); //restore cart

                $this->_redirect('checkout/cart');
            } else {
                if ($order->getState() !== Order::STATE_PENDING_PAYMENT) {
                    $this->getLogger()->addNotice('Order in state: ' . $order->getState());
                }
                $this->postToCheckout($order);
            }
            /* } catch (Exception $ex) {
              $this->getLogger()->addError('An exception was encountered in myfatoorah/checkout/index: ' . $ex->getMessage());
              $this->getLogger()->addError($ex->getTraceAsString());
              $this->getMessageManager()->addErrorMessage(__('Unable to start myfatoorah Checkout.')); */
        } catch (\Exception $ex) {
//            $this->getCheckoutHelper()->restoreQuote(); //restore cart
//            $this->getMessageManager()->addErrorMessage($ex->getMessage());
//            $this->_redirect('checkout/cart');

            $err = $ex->getMessage();

            $url = $this->getDataHelper()->getCancelledUrl($this->orderId, urlencode($err));

            $this->_redirect($url);
        }
    }

//---------------------------------------------------------------------------------------------------------------------------------------------------

    /** @var \Magento\Sales\Model\Order $order */
    private function getPayload($order, $gateway = null) {

        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $addressObj = $order->getShippingAddress();
        if (!is_object($addressObj)) {
            $addressObj = $order->getBillingAddress();
            if (!is_object($addressObj)) {
                throw new \Exception('Billing Address or Shipping address Data Should be set to create the invoice');
            }
        }

        $addressData = $addressObj->getData();

        $countryCode = isset($addressData['country_id']) ? $addressData['country_id'] : '';
        $city        = isset($addressData['city']) ? $addressData['city'] : '';
        $postcode    = isset($addressData['postcode']) ? $addressData['postcode'] : '';
        $region      = isset($addressData['region']) ? $addressData['region'] : '';

        $street1 = isset($addressData['street']) ? $addressData['street'] : '';
        $street  = trim(preg_replace("/[\n]/", ' ', $street1 . ' ' . $region));

        $phoneNo = isset($addressData['telephone']) ? $addressData['telephone'] : '';

        //$order->getCustomerName()  //$order->getCustomerFirstname() //$order->getCustomerLastname()
        $fName = !empty($addressObj->getFirstname()) ? $addressObj->getFirstname() : '';
        $lName = !empty($addressObj->getLastname()) ? $addressObj->getLastname() : '';

        $email = $order->getData('customer_email'); //$order->getCustomerEmail()


        $getLocale = $this->objectManager->get('Magento\Framework\Locale\Resolver');
        $haystack  = $getLocale->getLocale();
        $lang      = strstr($haystack, '_', true);

        $phone = $this->mfObj->getPhone($phoneNo);
        $url   = $this->getDataHelper()->getCompleteUrl();

        $userDefinedField = ($this->_gatewayConfig->getSaveCard() && $order->getCustomerId()) ? 'CK-' . $order->getCustomerId() : null;

        $shippingMethod = $order->getShippingMethod();
        $isShipping     = null;
        if (($shippingMethod == 'myfatoorah_shipping_1') || ($shippingMethod == 'myfatoorah_shippingDHL_myfatoorah_shippingDHL')) {
            $isShipping = 1;
        } else if (($shippingMethod == 'myfatoorah_shipping_2') || ($shippingMethod == 'myfatoorah_shippingAramex_myfatoorah_shippingAramex')) {
            $isShipping = 2;
        }

        $shippingConsignee = !$isShipping ? '' : array(
            'PersonName'   => "$fName $lName",
            'Mobile'       => trim($phone[1]),
            'EmailAddress' => $email,
            'LineAddress'  => trim(preg_replace("/[\n]/", ' ', $street . ' ' . $region)),
            'CityName'     => $city,
            'PostalCode'   => $postcode,
            'CountryCode'  => $countryCode
        );

        $currency = $this->getCurrencyData($gateway);

        //$invoiceItemsArr
//        $invoiceValue    = round($order->getBaseTotalDue() * $currency['rate'], 3);
//        $invoiceItemsArr = [[array('ItemName' => "Total Amount Order #$this->orderId", 'Quantity' => 1, 'UnitPrice' => $invoiceValue)]];

        $invoiceValue    = 0;
        $invoiceItemsArr = $this->getInvoiceItems($order, $currency['rate'], $isShipping, $invoiceValue);

        //ExpiryDate
        $expireAfter = $this->getPendingOrderLifetime(); //get Magento Pending Payment Order Lifetime (minutes)

        $ExpiryDate = new \DateTime('now', new \DateTimeZone('Asia/Kuwait'));
        $ExpiryDate->modify("+$expireAfter minute");
        return [
            'CustomerName'       => $fName . ' ' . $lName,
            'DisplayCurrencyIso' => $currency['code'], //$order->getOrderCurrencyCode(),
            'MobileCountryCode'  => trim($phone[0]),
            'CustomerMobile'     => trim($phone[1]),
            'CustomerEmail'      => $email,
            'InvoiceValue'       => "$invoiceValue",
            'CallBackUrl'        => $url,
            'ErrorUrl'           => $url,
            'Language'           => $lang,
            'CustomerReference'  => $this->orderId,
            'CustomerCivilId'    => null,
            'UserDefinedField'   => $userDefinedField,
            'ExpiryDate'         => $ExpiryDate->format('Y-m-d\TH:i:s'),
            'SourceInfo'         => 'Magento2 ' . $this->objectManager->get('Magento\Framework\App\ProductMetadataInterface')->getVersion() . ' - ' . $this->getGatewayConfig()->getCode() . ' ' . $this->getGatewayConfig()->getVersion(),
            'CustomerAddress'    => [
                'Block'               => '',
                'Street'              => '',
                'HouseBuildingNo'     => '',
                'Address'             => $city . ', ' . $region . ', ' . $postcode,
                'AddressInstructions' => $street
            ],
            'ShippingConsignee'  => $shippingConsignee,
            'ShippingMethod'     => $isShipping,
            'InvoiceItems'       => $invoiceItemsArr
        ];
    }

//---------------------------------------------------------------------------------------------------------------------------------------------------

    /** @var \Magento\Sales\Model\Order $order */
    function getInvoiceItems($order, $currencyRate, $isShipping, &$amount) {

        /** @var \Magento\Framework\App\Config\ScopeConfigInterface $ScopeConfigInterface */
        $ScopeConfigInterface = $this->getObjectManager()->create('\Magento\Framework\App\Config\ScopeConfigInterface');

        $weightUnit = $ScopeConfigInterface->getValue('general/locale/weight_unit', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $weightRate = ($isShipping) ? $this->mfObj->getWeightRate($weightUnit) : 1;

        /** @var \Magento\Sales\Api\Data\OrderItemInterface[]  $items */
        $items = $order->getAllVisibleItems();
        foreach ($items as $item) {
            $itemPrice = round($item->getBasePrice() * $currencyRate, 3);
            $qty       = intval($item->getQtyOrdered());

            $invoiceItemsArr[] = [
                'ItemName'  => $item->getName(),
                'Quantity'  => $qty,
                'UnitPrice' => "$itemPrice",
                'weight'    => ($isShipping) ? $item->getWeight() * $weightRate : 0,
                'Width'     => ($isShipping) ? $item->getProduct()->getData('width') : 0,
                'Height'    => ($isShipping) ? $item->getProduct()->getData('height') : 0,
                'Depth'     => ($isShipping) ? $item->getProduct()->getData('depth') : 0,
            ];
            $amount            += round($itemPrice * $qty, 3);
        }


        $shipping = $order->getBaseShippingAmount() + $order->getBaseShippingTaxAmount();
        if (!empty($shipping) && !$isShipping) {
            $itemPrice         = round($shipping * $currencyRate, 3);
            $invoiceItemsArr[] = ['ItemName' => 'Shipping Amount', 'Quantity' => '1', 'UnitPrice' => "$itemPrice", 'Weight' => '0', 'Width' => '0', 'Height' => '0', 'Depth' => '0'];

            $amount += $itemPrice;
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



        /*
          $this->log->info(print_r('FeeAmount' . $order->getBaseMageworxFeeAmount(),1));
          $this->log->info(print_r('FeeInvoiced' . $order->getBaseMageworxFeeInvoiced(),1));
          $this->log->info(print_r('FeeCancelled' . $order->getBaseMageworxFeeCancelled(),1));
          $this->log->info(print_r('FeeTaxAmount' . $order->getBaseMageworxFeeTaxAmount(),1));
          $this->log->info(print_r('FeeDetails' . $order->getMageworxFeeDetails(),1));
          $this->log->info(print_r('FeeRefunded' . $order->getMageworxFeeRefunded(),1));

          $this->log->info(print_r('ProductFeeAmount' . $order->getBaseMageworxProductFeeAmount(),1));
          $this->log->info(print_r('ProductFeeInvoiced' . $order->getBaseMageworxProductFeeInvoiced(),1));
          $this->log->info(print_r('ProductFeeCancelled' . $order->getBaseMageworxProductFeeCancelled(),1));
          $this->log->info(print_r('ProductFeeTaxAmount' . $order->getBaseMageworxProductFeeTaxAmount(),1));
          $this->log->info(print_r('ProductFeeDetails' . $order->getMageworxProductFeeDetails(),1));
          $this->log->info(print_r('ProductFeeRefunded' . $order->getMageworxProductFeeRefunded(),1));
         */
        return $invoiceItemsArr;
    }

//---------------------------------------------------------------------------------------------------------------------------------------------------
    function getCurrencyData($gateway) {
        /** @var \Magento\Store\Model\StoreManagerInterface  $StoreManagerInterface */
        $store = $this->objectManager->create('Magento\Store\Model\StoreManagerInterface')->getStore();

        $KWDcurrencyRate = (double) $store->getBaseCurrency()->getRate('KWD');
        if ($gateway == 'kn' && !empty($KWDcurrencyRate)) {
            $currencyCode = 'KWD';
            $currencyRate = $KWDcurrencyRate;
        } else {
            $currencyCode = $store->getBaseCurrencyCode();
            $currencyRate = 1;
            //(double) $this->objectManager->create('Magento\Store\Model\StoreManagerInterface')->getStore()->getCurrentCurrencyRate();
        }
        return ['code' => $currencyCode, 'rate' => $currencyRate];
    }

//---------------------------------------------------------------------------------------------------------------------------------------------------

    /** @var \Magento\Sales\Model\Order $order */
    private function postToCheckout($order) {

        $gatewayId = $this->getRequest()->get('pm') ?: 'myfatoorah';
        $sessionId = $this->getRequest()->get('sid') ?: null;

        $this->mfObj->log($gatewayId);
        $this->mfObj->log($sessionId);

        if (!$sessionId && !$gatewayId) {
            throw new \Exception('Invalid Payment Session');
        }
        $curlData = $this->getPayload($order);
        $data     = $this->mfObj->getInvoiceURL($curlData, $gatewayId, $this->orderId, $sessionId);

        //save the invoice id in myfatoorah_invoice table 
        $mf = $this->objectManager->create('MyFatoorah\Gateway\Model\MyfatoorahInvoice');
        $mf->addData([
            'order_id'     => $this->orderId,
            'invoice_id'   => $data['invoiceId'],
            'gateway_name' => 'MyFatoorah',
            'invoice_url'  => ($sessionId) ? '' : $data['invoiceURL'],
        ]);
        $mf->save();
        $this->_redirect($data['invoiceURL']);
    }

//---------------------------------------------------------------------------------------------------------------------------------------------------
}
