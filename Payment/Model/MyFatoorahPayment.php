<?php

namespace MyFatoorah\Payment\Model;

use MyFatoorah\Payment\Helper\Crypto;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Response\HandlerInterface;
use MyFatoorah\Library\PaymentMyfatoorahApiV2;

class MyFatoorahPayment extends \Magento\Payment\Model\Method\AbstractMethod implements HandlerInterface {

    public $_isGateway               = true;
    public $_canRefund               = true;
    public $_canRefundInvoicePartial = true;
    public $_canCapture              = true;
    public $_canCapturePartial       = true;
    public $_scopeConfig;

//---------------------------------------------------------------------------------------------------------------------------------------------------
    public function __construct(
            \Magento\Framework\Model\Context $context,
            \Magento\Framework\Registry $registry,
            \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
            \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
            \Magento\Payment\Helper\Data $paymentData,
            \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
            \Magento\Payment\Model\Method\Logger $logger
    ) {
        parent::__construct(
                $context,
                $registry,
                $extensionFactory,
                $customAttributeFactory,
                $paymentData,
                $scopeConfig,
                $logger
        );
        $this->_scopeConfig = $scopeConfig;
    }

//---------------------------------------------------------------------------------------------------------------------------------------------------
    public function handle(array $handlingSubject, array $response) {

//        $refundUrl  = $response['GATEWAY_REFUND_GATEWAY_URL'];
//        $myfatoorah = $response['GATEWAY_Myfatoorah_OBJ'];
//
//        $payment = $handlingSubject['payment']->getPayment();
//        if (empty($payment) || empty($payment->getData('creditmemo'))) {
//            throw new LocalizedException(__('We can\'t issue a refund transaction because there is no capture transaction.'));
//        }
//
//        $invoice       = $payment->getData()['creditmemo']->getData('invoice');
//        $transactionId = $invoice->getData('transaction_id');
//        $orderId       = $invoice->getData('order_id');
//        $currencyCode  = $invoice->getData('order_currency_code');
//
//        $rate = $myfatoorah->getCurrencyRate($currencyCode);
//
//        $refund_details = array(
//            'KeyType'                 => 'InvoiceId',
//            'Key'                     => $transactionId,
//            'RefundChargeOnCustomer'  => false,
//            'ServiceChargeOnCustomer' => false,
//            'Amount'                  => $handlingSubject['amount'] / $rate,
//            'Comment'                 => 'Refund',
//        );
//
//        $json = $myfatoorah->callAPI($refundUrl, $refund_details, $orderId, 'Make Refund');
//
//        if ($json->IsSuccess == true) {
//            return $this;
//        }
        //todo save the refund id
        //to do add the comment to myfatoorah
    }

//---------------------------------------------------------------------------------------------------------------------------------------------------
}
