<?php

namespace MyFatoorah\Gateway\Model\ResourceModel\MyfatoorahInvoice;

use MyFatoorah\Gateway\Model\MyfatoorahInvoice as MyfatoorahInvoiceModel;
use MyFatoorah\Gateway\Model\ResourceModel\MyfatoorahInvoice as MyfatoorahInvoiceResourceModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection {

//    protected $_idFieldName = 'id';

    protected function _construct() {
        $this->_init(
                MyfatoorahInvoiceModel::class,
                MyfatoorahInvoiceResourceModel::class
        );
    }

}