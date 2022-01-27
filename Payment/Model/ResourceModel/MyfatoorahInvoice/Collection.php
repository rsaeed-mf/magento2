<?php

namespace MyFatoorah\Payment\Model\ResourceModel\MyfatoorahInvoice;

use MyFatoorah\Payment\Model\MyfatoorahInvoice as MyfatoorahInvoiceModel;
use MyFatoorah\Payment\Model\ResourceModel\MyfatoorahInvoice as MyfatoorahInvoiceResourceModel;
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