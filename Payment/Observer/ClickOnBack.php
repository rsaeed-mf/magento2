<?php

namespace MyFatoorah\Payment\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Checkout\Model\Session\Proxy;
use Magento\Sales\Model\Order;

class ClickOnBack implements ObserverInterface {

    private $checkoutSession;

//---------------------------------------------------------------------------------------------------------------------------------------------------
    public function __construct(Proxy $checkoutSession) {
        $this->checkoutSession = $checkoutSession;
    }

//---------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * 
     * @param Observer $observer
     * @return boolean
     */
    public function execute(Observer $observer) {
        $lastRealOrder = $this->checkoutSession->getLastRealOrder();
        if ($lastRealOrder->getPayment()) {
            if ($lastRealOrder->getData('state') === Order::STATE_PENDING_PAYMENT && $lastRealOrder->getData('status') === Order::STATE_PENDING_PAYMENT) {
                $this->checkoutSession->restoreQuote();
            }
        }
        return true;
    }

//---------------------------------------------------------------------------------------------------------------------------------------------------
}
