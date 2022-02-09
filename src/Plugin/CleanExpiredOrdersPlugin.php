<?php

namespace MyFatoorah\Gateway\Plugin;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;
use MyFatoorah\Gateway\Controller\Checkout\Success;
use MyFatoorah\Gateway\Library\PaymentMyfatoorahApiV2;
use Zend\Log\Logger;

/**
 * The class that provides the functionality of checking MyFatoorah order statuses before cleaning expired quotes by cron
 */
class CleanExpiredOrdersPlugin {

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var CollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var Success
     */
    private $mf;

    /**
     * @var Logger
     */
    private $log;

//---------------------------------------------------------------------------------------------------------------------------------------------------
    public function __construct() {
        $objectManager = ObjectManager::getInstance();

        //used to list stores
        $this->storeManager = $objectManager->get(StoreManagerInterface::class);
        $this->scopeConfig  = $objectManager->get(ScopeConfigInterface::class);

        //used in list orders
        $this->orderCollectionFactory = $objectManager->get(CollectionFactory::class);

        //used in check MyFatoorah Status
        $this->mf = $objectManager->get(Success::class);

        //used in logging
        $this->log = new Logger();
        $this->log->addWriter(new \Zend\Log\Writer\Stream(BP . '/var/log/myfatoorah.log'));
    }

//---------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Clean expired quotes (cron process)
     *
     * @return void
     */
    public function beforeExecute() {

        $this->log->info('-------------------------------------------------------------------------------------------------------------');
        $this->log->info('In Cron Job: checkStatusForPendingOrders');

        $stores = $this->storeManager->getStores(true);

        /** @var $store \Magento\Store\Model\Store */
        foreach ($stores as $storeId => $store) {
            try {

                //get store needed config value
                $path     = 'payment/myfatoorah_payment/';
                $lifetime = $this->scopeConfig->getValue('sales/orders/delete_pending_after', ScopeInterface::SCOPE_STORE, $storeId);

                $apiKey      = $this->scopeConfig->getValue($path . 'api_key', ScopeInterface::SCOPE_STORE, $storeId);
                $isTesting   = $this->scopeConfig->getValue($path . 'is_testing', ScopeInterface::SCOPE_STORE, $storeId);
                $countryMode = $this->scopeConfig->getValue($path . 'countryMode', ScopeInterface::SCOPE_STORE, $storeId);

                $this->mfObj = new PaymentMyfatoorahApiV2($apiKey, $countryMode, $isTesting, $this->log, 'info');

                $this->checkPendingOrderByStore($storeId, $lifetime);
            } catch (NoSuchEntityException $ex) {
                // Store doesn't really exist, so move on.
                continue;
            }
        }
        $this->log->info('-------------------------------------------------------------------------------------------------------------');
    }

//---------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Get pending orders within the life time
     * 
     * @param type $storeId
     * @param type $lifetime
     */
    public function checkPendingOrderByStore($storeId, $lifetime) {
        /** @var $orders \Magento\Sales\Model\ResourceModel\Order\Collection */
        $orders = $this->orderCollectionFactory->create();
        $orders->addFieldToFilter('store_id', $storeId);
        $orders->addFieldToFilter('status', Order::STATE_PENDING_PAYMENT);
        $orders->getSelect()->where(
                new \Zend_Db_Expr('TIME_TO_SEC(TIMEDIFF(CURRENT_TIMESTAMP, `updated_at`)) >= ' . $lifetime * 60)
        );

        //check MyFatoorah status
        $this->checkMFStatus($orders);
    }

//---------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * 
     * @param \Magento\Sales\Model\ResourceModel\Order\Collection $orders
     * @return type
     */
    public function checkMFStatus($orders) {
        /** @var \Magento\Sales\Model\Order $order */
        foreach ($orders as $order) {
            $orderId = $order->getRealOrderId();

            $collection = $this->mf->mfInvoiceFactory->create()->addFieldToFilter('order_id', $orderId);
            $item       = $collection->getFirstItem()->getData();
            if (empty($item['invoice_id'])) {
                continue;
            }

            $invoiceId = $item['invoice_id'];

            $this->log->info("Order #$orderId ----- Cron Job - Check Order Status with Invoice Id #$invoiceId");
            try {
                $this->mf->checkStatus($invoiceId, 'InvoiceId', $this->mfObj, '-Cron');
            } catch (\Exception $ex) {
                $this->log->info('In Cron Exception Block: ' . $ex->getMessage());
            }
        }
    }

//---------------------------------------------------------------------------------------------------------------------------------------------------
}
