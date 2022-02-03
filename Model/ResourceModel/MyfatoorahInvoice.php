<?php

namespace MyFatoorah\Gateway\Model\ResourceModel;

class MyfatoorahInvoice extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb {

    public function _construct() {
        $this->_init('myfatoorah_invoice', 'id');
    }

}
