<?php

namespace MyFatoorah\Gateway\Model;

class MyfatoorahInvoice extends \Magento\Framework\Model\AbstractModel {

    public function _construct() {
        $this->_init('MyFatoorah\Gateway\Model\ResourceModel\MyfatoorahInvoice');
    }

}