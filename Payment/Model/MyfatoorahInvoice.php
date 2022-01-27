<?php

namespace MyFatoorah\Payment\Model;

class MyfatoorahInvoice extends \Magento\Framework\Model\AbstractModel {

    public function _construct() {
        $this->_init('MyFatoorah\Payment\Model\ResourceModel\MyfatoorahInvoice');
    }

}