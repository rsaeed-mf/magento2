<?php

namespace MyFatoorah\Gateway\Model\Config\Source;

class GatewayAction implements \Magento\Framework\Option\ArrayInterface {

    /**
     * {@inheritdoc}
     */
    public function toOptionArray() {
        return array(
            ['value' => 'myfatoorah', 'label' => 'MyFatoorah Invoice Page (Redirect)'],
            ['value' => 'multigateways', 'label' => 'List All Enabled Gateways in Checkout Page'],
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray() {
        
    }

}
