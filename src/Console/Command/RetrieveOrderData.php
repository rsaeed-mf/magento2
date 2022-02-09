<?php

namespace MyFatoorah\Gateway\Console\Command;

//use Magento\Sales\Model\Order;
use Magento\Framework\App\ObjectManager;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use MyFatoorah\Gateway\Library\PaymentMyfatoorahApiV2;

class RetrieveOrderData extends Command {

    const NAME_ARGUMENT = 'OrderIds';

//---------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * {@inheritdoc}
     */
    protected function configure() {
        $this->setName('myfatoorah:update');
        $this->setDescription('Force update the status of the order even if it is confirmed, completed, shipped, or processing with the last invoice status in MyFatoorah vendor account.');
        $this->setDefinition([
            new InputArgument(self::NAME_ARGUMENT, InputArgument::IS_ARRAY, 'OrderIds')
        ]);
        parent::configure();
    }

//---------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $objectManager = ObjectManager::getInstance();

        //To avoid the Area code is not set of the send email command
        $state = $objectManager->get('\Magento\Framework\App\State');
        $state->setAreaCode(\Magento\Framework\App\Area::AREA_FRONTEND); /* \Magento\Framework\App\Area::AREA_ADMINHTML, depending on your needs */

        //Create Success Object
        $successObj = $objectManager->create('MyFatoorah\Gateway\Controller\Checkout\Success');

        $ScopeConfigInterface = $objectManager->create('\Magento\Framework\App\Config\ScopeConfigInterface');
        $scopeStore           = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

        //get db connection
        $connection = $objectManager->get('Magento\Framework\App\ResourceConnection')->getConnection('\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION');

        //get table name
        $deploymentConfig = $objectManager->get('Magento\Framework\App\DeploymentConfig');
        $prefix           = ($deploymentConfig->get('db/table_prefix'));
        $tableName        = $prefix . 'myfatoorah_invoice';

        //Get Pending orderss
        $this->_orderCollectionFactory = $objectManager->create('Magento\Sales\Model\ResourceModel\Order\CollectionFactory');
        $orders                        = $this->_orderCollectionFactory->create()
                ->addFieldToFilter('increment_id', ['in' => $input->getArgument(self::NAME_ARGUMENT)]);

        if (empty($orders->getAllIds())) {
            $output->writeln('No Order Recoreds found');
        }

        $log = new \Zend\Log\Logger();
        $log->addWriter(new \Zend\Log\Writer\Stream(BP . '/var/log/myfatoorah.log'));

        //Update Status
        /** @var \Magento\Sales\Model\Order $order */
        foreach ($orders as $order) {
            $orderId = $order->getRealOrderId();

            $result1 = $connection->fetchAll("SELECT invoice_id FROM $tableName WHERE order_id=$orderId");

            if (empty($result1[0]['invoice_id'])) {
                $output->writeln("Order #$orderId ----- Command - Not a MyFatoorah recored");
                continue;
            }

            $invoiceId = $result1[0]['invoice_id'];
            $output->writeln("Order #$orderId ----- Command - Check Order Status with Invoice Id #$invoiceId");

            try {

                $storeId = $order->getStoreId();
                $path    = 'payment/myfatoorah_payment/';

                $apiKey      = $ScopeConfigInterface->getValue($path . 'api_key', $scopeStore, $storeId);
                $isTesting   = $ScopeConfigInterface->getValue($path . 'is_testing', $scopeStore, $storeId);
                $countryMode = $ScopeConfigInterface->getValue($path . 'countryMode', $scopeStore, $storeId);

                $myfatoorah = new PaymentMyfatoorahApiV2($apiKey, $countryMode, $isTesting, $log, 'info');

                $mfStatus = $successObj->checkStatus($invoiceId, 'InvoiceId', $myfatoorah, '-Cmd', true);
                if ($mfStatus) {
                    $output->writeln("Order #$orderId ----- Command - Faild with error: $mfStatus");
                } else {
                    $output->writeln("Order #$orderId ----- Command - Success");
                }
            } catch (\Exception $ex) {
                $err = $ex->getMessage();
                //if ($err != 'Area code is not set') {
                $output->writeln("Order #$orderId ----- Command - Excption $err");
                //$output->writeln($ex->getTraceAsString());
                //}
            }
        }
    }

//---------------------------------------------------------------------------------------------------------------------------------------------------
}
