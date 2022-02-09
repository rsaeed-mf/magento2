<?php

namespace MyFatoorah\Gateway\Helper;

use Magento\Checkout\Model\Session;
use Magento\Sales\Model\Order;

/**
 * Checkout workflow helper
 *
 * Class Checkout
 * @package MyFatoorah\Gateway\Helper
 */
class Checkout {

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $session;

    /**
     * @param \Magento\Checkout\Model\Session $session
     */
    public function __construct(
            Session $session
    ) {
        $this->session = $session;
    }

    /**
     * Cancel last placed order with specified comment message
     *
     * @param string $comment Comment appended to order history
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return bool True if order cancelled, false otherwise
     */
    public function cancelCurrentOrder($comment) {
        $order = $this->session->getLastRealOrder();
        if ($order && $order->getId() && $order->getState() != Order::STATE_CANCELED) {
            $order->registerCancellation('MyFatoorah: ' . $comment)->save();

            return true;
        }

        return false;
    }

    /**
     * Restores quote (restores cart)
     *
     * @return bool
     */
    public function restoreQuote() {
        return $this->session->restoreQuote();
    }

}
