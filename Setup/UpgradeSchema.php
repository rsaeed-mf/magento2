<?php

namespace MyFatoorah\Gateway\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Ddl\Table;

class UpgradeSchema implements UpgradeSchemaInterface {

//---------------------------------------------------------------------------------------------------------------------------------------------------
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context) {
        $setup->startSetup();
        $conn      = $setup->getConnection();
        $tableName = $setup->getTable('myfatoorah_invoice');

        if (!$conn->isTableExists($tableName)) {
            $this->createMyfatoorahInvoiceTable($conn, $tableName);
        }
        $setup->endSetup();
    }

//---------------------------------------------------------------------------------------------------------------------------------------------------
    function createMyfatoorahInvoiceTable($conn, $tableName) {
        $table = $conn->newTable($tableName)
                ->addColumn(
                        'id',
                        Table::TYPE_BIGINT,
                        null,
                        ['primary' => true, 'identity' => true, 'unsigned' => true, 'nullable' => false]
                )
                ->addColumn(
                        'order_id',
                        Table::TYPE_TEXT,
                        32,
                        ['nullable' => false]
                )
                ->addColumn(
                        'invoice_id',
                        Table::TYPE_TEXT,
                        32,
                        ['nullable' => true]
                )
                ->addColumn(
                        'invoice_reference',
                        Table::TYPE_TEXT,
                        32,
                        ['nullable' => true],
                        'The Invoice Reference'
                )
                ->addColumn(
                        'invoice_url',
                        Table::TYPE_TEXT,
                        255,
                        ['nullable' => true],
                        'The Invoice or Payment URL'
                )
                ->addColumn(
                        'reference_id',
                        Table::TYPE_TEXT,
                        32,
                        ['nullable' => true],
                        'The Reference ID'
                )
                ->addColumn(
                        'track_id',
                        Table::TYPE_TEXT,
                        32,
                        ['nullable' => true],
                        'The Track ID'
                )->addColumn(
                        'authorization_id',
                        Table::TYPE_TEXT,
                        32,
                        ['nullable' => true],
                        'The Authorization ID'
                )->addColumn(
                        'gateway_transaction_id',
                        Table::TYPE_TEXT,
                        32,
                        ['nullable' => true],
                        'The used Payment Gateway Transaction ID'
                )
                ->addColumn(
                        'payment_id',
                        Table::TYPE_TEXT,
                        32,
                        ['nullable' => true],
                        'The Payment ID'
                )
                ->addColumn(
                        'gateway_name',
                        Table::TYPE_TEXT,
                        100,
                        ['nullable' => false, 'default' => 'myfatoorah'],
                        'The used Payment Gateway - name'
                )
                ->setOption('charset', 'utf8');
        $conn->createTable($table);
    }

//---------------------------------------------------------------------------------------------------------------------------------------------------
}
