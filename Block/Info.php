<?php

namespace MyFatoorah\Gateway\Block;

use Magento\Framework\Phrase;
use Magento\Payment\Block\ConfigurableInfo;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Gateway\ConfigInterface;
use MyFatoorah\Gateway\Model\ResourceModel\MyfatoorahInvoice\CollectionFactory;
use MyFatoorah\Gateway\Gateway\Config\Config;

/**
 * Class Info
 */
class Info extends ConfigurableInfo {

    /**
     * @var string
     */
    protected $_template = 'info/default.phtml';
    protected $mfInvoiceFactory;
    protected $gatewayConfig;

    public function __construct(
            CollectionFactory $mfInvoiceFactory,
            Config $mfconfig,
            Context $context,
            ConfigInterface $config,
            array $data = []
    ) {
        parent::__construct($context, $config, $data);
        $this->mfInvoiceFactory = $mfInvoiceFactory;
        $this->gatewayConfig    = $mfconfig;
    }

    /**
     * Returns label
     *
     * @param string $field
     *
     * @return Phrase
     */
    protected function getLabel($field) {
        return __($field);
    }

    //for emails
    public function getSpecificInformation() {

        $item = $this->getInvoiceData();
        if ($item) {
            $data = [
                'Invoice ID'   => $item['invoice_id'],
                'Invoice Ref.' => $item['invoice_reference'],
                'Gateway'      => $item['gateway_name'],
                'Invoice URL'  => $item['invoice_url'],
            ];

            if (isset($item['reference_id'])) {
                $data['Trans. Ref. ID'] = $item['reference_id'];
            }
            if (isset($item['track_id'])) {
                $data['Track ID'] = $item['track_id'];
            }
            if (isset($item['authorization_id'])) {
                $data['Authorization ID'] = $item['authorization_id'];
            }
            if (isset($item['gateway_transaction_id'])) {
                $data['Transaction ID'] = $item['gateway_transaction_id'];
            }
            if (isset($item['payment_id'])) {
                $data['Payment ID'] = $item['payment_id'];
            }
            return $data;
        }
    }

    //for Admin and user
    public function getMFInformation() {

        $item = $this->getInvoiceData();
        if ($item) {
            $data['Invoice ID'] = '<a href="' . $item['invoice_url'] . '">' . $item['invoice_id'] . '</a>';

            if (isset($item['invoice_reference'])) {
                $data['Invoice Ref.'] = $item['invoice_reference'];
            }

            $data['Gateway'] = $item['gateway_name'];

            if (isset($item['reference_id'])) {
                $data['Trans. Ref. ID'] = $item['reference_id'];
            }
            if (isset($item['track_id'])) {
                $data['Track ID'] = $item['track_id'];
            }
            if (isset($item['authorization_id'])) {
                $data['Auth. ID'] = $item['authorization_id'];
            }
            if (isset($item['gateway_transaction_id'])) {
                $data['Trans. ID'] = $item['gateway_transaction_id'];
            }
            if (isset($item['payment_id'])) {
                $data['Payment ID'] = $item['payment_id'];
            }



            return $data;
        }
    }

    public function getInvoiceData() {

        $mfOrder = $this->getInfo()->getOrder();
        $orderId = $mfOrder->getRealOrderId();
        if (!$orderId) {
            return;
        }

        $collection = $this->mfInvoiceFactory->create()->addFieldToFilter('order_id', $orderId);
        $items      = $collection->getData();

        if (isset($items[0])) {
            return $items[0];
        }
    }

}
