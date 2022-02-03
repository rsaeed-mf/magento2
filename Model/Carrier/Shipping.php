<?php

namespace MyFatoorah\Gateway\Model\Carrier;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Catalog\Model\Product;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
//use Magento\Shipping\Model\Config;
use Magento\Shipping\Model\Rate\ResultFactory;
//use Magento\Store\Model\ScopeInterface;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
//use Magento\Quote\Model\Quote\Address\RateResult\Method;
use Magento\Shipping\Model\Rate\Result;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Framework\App\ObjectManager;
use Psr\Log\LoggerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Module\Manager;
use MyFatoorah\Gateway\Library\ShippingMyfatoorahApiV2;

class Shipping extends AbstractCarrier implements CarrierInterface {

    /**

     * Carrier's code
     *
     * @var string
     */
    protected $_code = 'myfatoorah_shipping';

    /**
     * Whether this carrier has fixed rates calculation
     *
     * @var bool
     */
    protected $_isFixed = true;

    /**
     * @var Result
     */
    private $rateResultFactory;

    /**
     * @var MethodFactory
     */
    private $rateMethodFactory;

    /**
     *
     * @var \Zend\Log\Logger 
     */
    private $log;

    /**
     *
     * @var ShippingMyfatoorahApiV2 
     */
    private $sMF;

    /**
     *
     * @var array
     */
    private $mfShippingMethods = [1 => 'DHL', 2 => 'Aramex'];

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * 
     * @param StoreManagerInterface $storeManager
     * @param ResultFactory $resultFactory
     * @param MethodFactory $methodFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param ErrorFactory $rateErrorFactory
     * @param LoggerInterface $logger
     * @param array $data
     */
    public function __construct(
            Manager $moduleManager,
            StoreManagerInterface $storeManager,
            Product $productFactory,
            ResultFactory $resultFactory,
            MethodFactory $methodFactory,
            ScopeConfigInterface $scopeConfig,
            ErrorFactory $rateErrorFactory,
            LoggerInterface $logger,
            array $data = array()) {

        //initiate the parent constructor
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);

        $this->moduleManager     = $moduleManager;
        $this->storeManager      = $storeManager;
        $this->productFactory    = $productFactory;
        $this->rateResultFactory = $resultFactory;
        $this->rateMethodFactory = $methodFactory;
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Generates list of allowed carrier`s shipping methods
     * Displays on cart price rules page
     *
     * @return array
     * @api
     */
    public function getAllowedMethods() {
        return [
            $this->getCarrierCode() => __('DHL'),
            $this->getCarrierCode() => __('Aramex')
        ];
    }

    //todo ned to fix the cart page
//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Collect and get rates for storefront
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @param RateRequest $request
     * @return DataObject|bool|null
     * @api
     */
    public function collectRates(RateRequest $request) {

        //logging
        $writer    = new \Zend\Log\Writer\Stream(BP . '/var/log/myfatoorah_shipping.log');
        $this->log = new \Zend\Log\Logger();
        $this->log->addWriter($writer);
        $this->log->info("-----------------------------------------------------------------------------------------------------");

        $configData = $this->getMFPaymentConfigData();

        //return false if no MyFatoorah payment methold is not enabled
        if (!$configData) {
            return false;
        }

        $this->sMF = new ShippingMyfatoorahApiV2($configData['apiKey'], $configData['countryMode'], $configData['isTesting'], $this->log, 'info');

        try {
//            $currency     = $this->storeManager->getStore()->getCurrentCurrency()->getCode();
            $currency     = $this->storeManager->getStore()->getBaseCurrency()->getCode();
            $currencyRate = $this->sMF->getCurrencyRate($currency);

            $curlData = [
                'Items'       => $this->getInvoiceItems($request),
                'CityName'    => $request->getDestCity(),
                'PostalCode'  => $request->getDestPostcode(),
                'CountryCode' => $request->getDestCountryId()
            ];

            $rateResult = $this->rateResultFactory->create();

            $configMethods    = $this->getConfigData('methods');
            $availableMethods = empty($configMethods) ? [] : explode(',', $configMethods);

            foreach ($availableMethods as $id) {
                $curlData['ShippingMethod'] = $id;

                $json = $this->sMF->calculateShippingCharge($curlData);
                
                $realVal        = floor($json->Data->Fees * 1000) / 1000;
                
                error_log('$realVal ' . $realVal);

                $shippingAmount = $currencyRate * $realVal;
                
                $store = \Magento\Framework\App\ObjectManager::getInstance()->create('Magento\Store\Model\StoreManagerInterface')->getStore();
                
                
                 error_log('$shippingAmount ' . $shippingAmount);
                if ($shippingAmount) {
                    $rateResult->append($this->createShippingMethod($shippingAmount, $id, $this->mfShippingMethods[$id]));
                }
            }

            return $rateResult;
        } catch (\Exception $ex) {
            return $this->getError($ex->getMessage());
        }
    }

//-----------------------------------------------------------------------------------------------------------------------------------------
    private function getError($message) {
        /* @var \Magento\Quote\Model\Quote\Address\RateResult\Error $error */
        $error = $this->_rateErrorFactory->create();
        $error->setCarrier($this->getCarrierCode());
        $error->setCarrierTitle($this->getConfigData('title'));
        $error->setErrorMessage($message);
        return $error;
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     *     
     * @param RateRequest $request
     * @return type
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getInvoiceItems($request) {
        $items = $request->getAllItems();

        $weightUnit = $this->_scopeConfig->getValue('general/locale/weight_unit', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $weightRate = $this->sMF->getWeightRate($weightUnit);

        $invoiceItemsArr = array();
        foreach ($items as $item) {
            /* @var $item \Magento\Quote\Model\Quote\Item */

            //get dimensions
            $productId = $item->getProductId();
            $product   = $this->productFactory->load($productId);

            $width  = $product->getData('width');
            $height = $product->getData('height');
            $depth  = $product->getData('depth');

            //get weight
            $weight = $item->getWeight() * $weightRate;
            if (($item->getProductType() != 'downloadable') && (!$weight || !$width || !$height || !$depth)) {
                $err = 'Kindly, contact the site admin to set weight and dimensions for ';
                $this->log->info("DimensionsAdd - Error: $err" . $item->getName());

                $msgErr = __($err) . $item->getName();
                throw new \Magento\Framework\Exception\LocalizedException(__($msgErr));
            }

            $invoiceItemsArr[] = array(
                'ProductName' => $item->getName(),
                "Description" => $item->getName(),
                'weight'      => $weight,
                'Width'       => $width,
                'Height'      => $height,
                'Depth'       => $depth,
                'Quantity'    => round($item->getQty(), 0),
                'UnitPrice'   => $item->getPrice(),
            );
        }
        return $invoiceItemsArr;
    }

//-----------------------------------------------------------------------------------------------------------------------------------------
    private function createShippingMethod($shippingAmount, $id, $name) {

        $method = $this->rateMethodFactory->create();

        //Set carrier's data
        $method->setCarrier($this->getCarrierCode());
        $method->setCarrierTitle($this->getConfigData('title'));

        //Set method under Carrier
        $method->setMethod($id);
        $method->setMethodTitle(__($name));

        //set price
        $method->setPrice($shippingAmount);
        $method->setCost($shippingAmount);

        return $method;
    }

//-----------------------------------------------------------------------------------------------------------------------------------------    

    /**
     * Retrieve information from carrier configuration
     *
     * @param   string $field
     * @return  false|string
     */
    private function getMFPaymentConfigData() {

//        $store = $this->getStore();
        $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        
        if ($this->moduleManager->isEnabled('MyFatoorah_Gateway') && $this->_scopeConfig->getValue('payment/myfatoorah_payment/active', $scope)) {
            $path = 'payment/myfatoorah_payment/';
        } else if ($this->moduleManager->isEnabled('MyFatoorah_MyFatoorahPaymentGateway') && $this->_scopeConfig->getValue('payment/myfatoorah_gateway/active', $scope)) {
            $path = 'payment/myfatoorah_gateway/';
        } else if ($this->moduleManager->isEnabled('MyFatoorah_EmbedPay') && $this->_scopeConfig->getValue('payment/embedpay/active', $scope)) {
            $path = 'payment/embedpay/';
        } else {
            return false;
        }

        $data['apiKey']      = $this->_scopeConfig->getValue($path . 'api_key', $scope);
        $data['isTesting']   = $this->_scopeConfig->getValue($path . 'is_testing', $scope);
        $data['countryMode'] = $this->_scopeConfig->getValue($path . 'countryMode', $scope);

        return $data;
    }

//-----------------------------------------------------------------------------------------------------------------------------------------
}
