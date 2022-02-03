<?php

namespace MyFatoorah\Gateway\Model;

use Magento\Framework\App\ObjectManager;
use MyFatoorah\Gateway\Controller\Checkout\Success;
use Magento\Sales\Model\Order;
use MyFatoorah\Gateway\Library\PaymentMyfatoorahApiV2;

class WebHook {

    private $successObj;

//-----------------------------------------------------------------------------------------------------------------------------------------
    public function __construct() {

        $objectManager    = ObjectManager::getInstance();
        $this->successObj = $objectManager->get(Success::class);

        $this->ScopeConfigInterface = $objectManager->create('\Magento\Framework\App\Config\ScopeConfigInterface');
        $this->scopeStore           = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

        $this->orderCollection = $objectManager->create(Order::class);

        $this->log = new \Zend\Log\Logger();
        $this->log->addWriter(new \Zend\Log\Writer\Stream(BP . '/var/log/myfatoorah.log'));
    }

//-----------------------------------------------------------------------------------------------------------------------------------------    

    /**
     * {@inheritdoc}
     */
    public function execute($EventType, $Event, $DateTime, $CountryIsoCode, $Data) {

        //to allow the callback code run 1st. 
        sleep(30);

        if ($EventType != 1) {
            return;
        }

        $this->TransactionsStatusChanged($Data);
    }

//-----------------------------------------------------------------------------------------------------------------------------------------
    function TransactionsStatusChanged($data) {

        $orderId = $data['CustomerReference'];
        try {
            //get the order to get its store
            $order = $this->orderCollection->loadByIncrementId($orderId);
            if (!$order->getId()) {
                throw new \Exception('MyFatoorah returned an order that could not be retrieved');
            }

            //get the order store config
            $path        = 'payment/myfatoorah_payment/';
            $storeId     = $order->getStoreId();
            
            $apiKey      = $this->ScopeConfigInterface->getValue($path . 'api_key', $this->scopeStore, $storeId);
            $isTesting   = $this->ScopeConfigInterface->getValue($path . 'is_testing', $this->scopeStore, $storeId);
            $countryMode = $this->ScopeConfigInterface->getValue($path . 'countryMode', $this->scopeStore, $storeId);
            
            $webhookSecretKey = $this->ScopeConfigInterface->getValue($path . 'webhookSecretKey', $this->scopeStore, $storeId);

            //get lib object
            $myfatoorah                 = new PaymentMyfatoorahApiV2($apiKey, $countryMode, $isTesting, $this->log, 'info');
            $myfatoorah->webhookLogPath = BP . '/var/log/'; //@todo remove the logWebhook function
            //get MyFatoorah-Signature from request headers
            $request_headers            = apache_request_headers();
            $myfatoorahSignature        = $request_headers['MyFatoorah-Signature'];

            //validate signature
            if (!$myfatoorah->validateSignature($data, $webhookSecretKey, $myfatoorahSignature)){
                return;
            }

            //update order status
            $this->successObj->checkStatus($data['InvoiceId'], 'InvoiceId', $myfatoorah, '-WebHook');
        } catch (\Exception $ex) {
            $this->log->info("Order #$orderId ----- WebHook - Excption " . $ex->getMessage());
        }
    }

//-----------------------------------------------------------------------------------------------------------------------------------------
}
