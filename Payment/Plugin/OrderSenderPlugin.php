<?php

namespace MyFatoorah\Payment\Plugin;

use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order;

class OrderSenderPlugin {

//---------------------------------------------------------------------------------------------------------------------------------------------------
    public function aroundSend(OrderSender $subject, callable $proceed, Order $order, $forceSyncMode = false) {
        $payment = $order->getPayment()->getMethodInstance()->getCode();

        if ($payment === 'myfatoorah_payment' && $order->getState() === Order::STATE_PENDING_PAYMENT) {
            return false;
        }

        return $proceed($order, $forceSyncMode);
    }

//---------------------------------------------------------------------------------------------------------------------------------------------------
}
