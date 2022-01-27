<?php

namespace MyFatoorah\Library; 

use Exception;

/**
 * Class MyfatoorahApiV2 is responsible for handling calling MyFatoorah API endpoints. Also, It has necessary library functions that help in providing the correct parameters used endpoints.
 *
 * MyFatoorah offers a seamless business experience by offering a technology put together by our tech team. This enables smooth business operations involving sales activity, product invoicing, shipping, and payment processing. MyFatoorah invoicing and payment gateway solution trigger your business to greater success at all levels in the new age world of commerce. Leverage your sales and payments at all e-commerce platforms (ERPs, CRMs, CMSs) with transparent and slick applications that are well-integrated into social media and telecom services. For every closing sale click, you make a business function gets done for you, along with generating factual reports and statistics to fine-tune your business plan with no-barrier low-cost.
 * Our technology experts have designed the best GCC E-commerce solutions for the native financial instruments (Debit Cards, Credit Cards, etc.) supporting online sales and payments, for events, shopping, mall, and associated services.
 *
 * Created by MyFatoorah http://www.myfatoorah.com/
 * Developed By tech@myfatoorah.com
 * Date: 03/03/2021
 * Time: 12:00
 *
 * API Documentation on https://myfatoorah.readme.io/docs
 * Library Documentation and Download link on https://myfatoorah.readme.io/docs/php-library
 * 
 * @author MyFatoorah <tech@myfatoorah.com>
 * @copyright 2021 MyFatoorah, All rights reserved
 * @license GNU General Public License v3.0
 */
 
class MyfatoorahApiV2 {
//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Provides the URL used to connect the MyFatoorah API wether is on the test server or the live server
     * 
     * @var string 
     */
    protected $apiURL = '';

    /**
     * The API Token Key is the authentication which identify a user that is using the app
     * To generate one follow instruction here https://myfatoorah.readme.io/docs/live-token
     *  
     * @var string
     */
    private $apiKey;

    /**
     * This is the file name or the logger object
     * It will be used in logging the payment/shipping events to help in debugging and monitor the process and connections.
     * 
     * @var sting|object
     */
    private $loggerObj;

    /**
     * If $loggerObj is set as a logger object, you should set this var with the function name that will be used in the debugging.
     * 
     * @var string 
     */
    private $loggerFunc;

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Constructor
     * Initiate new MyFatoorah API process
     *  
     * @param string        $apiKey         The API Token Key is the authentication which identify a user that is using the app. To generate one follow instruction here https://myfatoorah.readme.io/docs/live-token.
     * @param string        $countryMode    Select the country mode.
     * @param boolean       $isTest         If This set to true, the process will be on the test mode. Set it to false for live mode.
     * @param sting|object  $loggerObj      It is optional. This is the file name or the logger object. It will be used in logging the payment/shipping events to help in debugging and monitor the process and connections. Leave it null, if you done't want to log the events.
     * @param string        $loggerFunc     It is optional. If $loggerObj is set as a logger object, you should set this var with the function name that will be used in the debugging.
     */
    public function __construct($apiKey, $countryMode = 'KWT', $isTest = false, $loggerObj = null, $loggerFunc = null) {

        $mfCountries = $this->getMyFatoorahCountries();

        $code = strtoupper($countryMode);
        if (isset($mfCountries[$code])) {
            $this->apiURL = ($isTest) ? $mfCountries[$code]['testv2'] : $mfCountries[$code]['v2'];
        }

        $this->apiKey     = trim($apiKey);
        $this->loggerObj  = $loggerObj;
        $this->loggerFunc = $loggerFunc;
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * 
     * @param string            $url        It is the MyFatoorah API endpoint URL
     * @param array             $postFields It is the array of the POST request parameters. It should be set to null if the request is GET.
     * @param integer|string    $orderId    It is optional. It is the order id or the payment id of the process. It will be used in the events logging.
     * @param string            $function   It is optional. The function name that made the request. It will be used in the events logging.
     * @return object           The response object as the result of a successful calling to the API.
     * @throws Exception        Throw exception if there is any curl error or a validation error in the MyFatoorah API endpoint URL
     */
    public function callAPI($url, $postFields = null, $orderId = null, $function = null) {

        //to prevent json_encode adding lots of decimal digits
        ini_set('precision', 14);
        ini_set('serialize_precision', -1);

        $request = isset($postFields) ? 'POST' : 'GET';
        $fields  = json_encode($postFields);

        $msgLog = "Order #$orderId ----- $function";

        if ($function != 'Direct Payment') {
            $this->log("$msgLog - Request: $fields");
        }

        //***************************************
        //call url
        //***************************************
        $curl = curl_init($url);

        curl_setopt_array($curl, array(
            CURLOPT_CUSTOMREQUEST  => $request,
            CURLOPT_POSTFIELDS     => $fields,
            CURLOPT_HTTPHEADER     => array("Authorization: Bearer $this->apiKey", 'Content-Type: application/json'),
            CURLOPT_RETURNTRANSFER => true,
        ));

        $res = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        //example set a local ip to host apitest.myfatoorah.com
        if ($err) {
            $this->log("$msgLog - cURL Error: $err");
            throw new Exception($err);
        }

        $this->log("$msgLog - Response: $res");

        $json = json_decode($res);

        //***************************************
        //check for errors
        //***************************************

        $error = $this->{"getAPIError$request"}($json, $res);
        if ($error) {
            $this->log("$msgLog - Error: $error");
            throw new Exception($error);
        }

        //***************************************
        //Success 
        //***************************************
        return $json;
    }

//------------------------------------------------------------------------------

    /**
     * Handles POST Endpoint Errors Function
     * @param type $json
     * @param type $res
     * @return type
     */
    function getAPIErrorPOST($json, $res) {
        if (isset($json->IsSuccess) && $json->IsSuccess == true) {
            return null;
        }

        //Check for the errors
        if (isset($json->ValidationErrors) || isset($json->FieldsErrors)) {
            //$err = implode(', ', array_column($json->ValidationErrors, 'Error'));

            $errorsObj = isset($json->ValidationErrors) ? $json->ValidationErrors : $json->FieldsErrors;
            $blogDatas = array_column($errorsObj, 'Error', 'Name');

            $err = implode(', ', array_map(function ($k, $v) {
                        return "$k: $v";
                    }, array_keys($blogDatas), array_values($blogDatas)));
        } else if (isset($json->Data->ErrorMessage)) {
            $err = $json->Data->ErrorMessage;
        }

        //if not get the message. this is due that sometimes errors with ValidationErrors has Error value null so either get the "Name" key or get the "Message"
        //example {"IsSuccess":false,"Message":"Invalid data","ValidationErrors":[{"Name":"invoiceCreate.InvoiceItems","Error":""}],"Data":null}
        //example {"Message":"No HTTP resource was found that matches the request URI 'https://apitest.myfatoorah.com/v2/SendPayment222'.","MessageDetail":"No route providing a controller name was found to match request URI 'https://apitest.myfatoorah.com/v2/SendPayment222'"}
        if (empty($err)) {
            $err = (isset($json->Message)) ? $json->Message : (!empty($res) ? $res : 'Kindly, review your MyFatoorah admin configuration due to a wrong entry.');
        }
        return $err;
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Handles GET Endpoint Errors Function
     * 
     * @param type $json
     * @param type $res
     * @return type
     */
    function getAPIErrorGET($json, $res) {
        $stripHtmlStr = strip_tags($res);
        if ($res != $stripHtmlStr) {
            return trim(preg_replace('/\s+/', ' ', $stripHtmlStr));
        }

        if (!($json) || (isset($json->Message))) {
            $message = isset($json->Message) ? $json->Message : '';
            return $message . ' Kindly, review your MyFatoorah admin configuration due to a wrong entry.';
        }
        return null;
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Returns the country code and the phone after applying MyFatoorah restriction
     * 
     * Matching regular expression pattern: ^(?:(\+)|(00)|(\\*)|())[0-9]{3,14}((\\#)|())$
     * if (!preg_match('/^(?:(\+)|(00)|(\\*)|())[0-9]{3,14}((\\#)|())$/iD', $inputString))
     * String length: inclusive between 0 and 11
     * 
     * @param string        $inputString It is the input phone number provide by the end user.
     * @return array        That contains the phone code in the 1st element the the phone number the the 2nd element.
     * @throws Exception    Throw exception if the input length is less than 3 chars or long than 14 chars.
     */
    public function getPhone($inputString) {

        //remove any arabic digit
        $newNumbers = range(0, 9);

        $persianDecimal = array('&#1776;', '&#1777;', '&#1778;', '&#1779;', '&#1780;', '&#1781;', '&#1782;', '&#1783;', '&#1784;', '&#1785;'); // 1. Persian HTML decimal
        $arabicDecimal  = array('&#1632;', '&#1633;', '&#1634;', '&#1635;', '&#1636;', '&#1637;', '&#1638;', '&#1639;', '&#1640;', '&#1641;'); // 2. Arabic HTML decimal
        $arabic         = array('٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'); // 3. Arabic Numeric
        $persian        = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'); // 4. Persian Numeric

        $string0 = str_replace($persianDecimal, $newNumbers, $inputString);
        $string1 = str_replace($arabicDecimal, $newNumbers, $string0);
        $string2 = str_replace($arabic, $newNumbers, $string1);
        $string3 = str_replace($persian, $newNumbers, $string2);

        //Keep Only digits
        $string4 = preg_replace('/[^0-9]/', '', $string3);

        //remove 00 at start
        if (strpos($string4, '00') === 0) {
            $string4 = substr($string4, 2);
        }

        if (!$string4) {
            return ['', ''];
        }

        //check for the allowed length
        $len = strlen($string4);
        if ($len < 3 || $len > 14) {
            throw new Exception('Phone Number lenght must be between 3 to 14 digits');
        }

        //get the phone arr
        if (strlen(substr($string4, 3)) > 3) {
            return [
                substr($string4, 0, 3),
                substr($string4, 3)
            ];
        } else {
            return [
                '',
                $string4
            ];
        }
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * It will log the payment/shipping process events
     * 
     * @param string $msg It is the string message that will be written in the log file
     */
    public function log($msg) {

        if (!$this->loggerObj) {
            return;
        }
        if (is_string($this->loggerObj)) {
            error_log(PHP_EOL . date('d.m.Y h:i:s') . ' - ' . $msg, 3, $this->loggerObj);
        } else if (method_exists($this->loggerObj, $this->loggerFunc)) {
            $this->loggerObj->{$this->loggerFunc}($msg);
        }
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Get the rate that will convert the given weight unit to MyFatoorah default weight unit.
     * 
     * @param string        $unit It is the weight unit used. Weight must be in kg, g, lbs, or oz. Default is kg.
     * @return real         The conversion rate that will convert the given unit into the kg. 
     * @throws Exception    Throw exception if the input unit is not support. Weight must be in kg, g, lbs, or oz. Default is kg.
     */
    public function getWeightRate($unit) {

        $unit1 = strtolower($unit);
        if ($unit1 == 'kg') {
            $rate = 1; //kg is the default
        } else if ($unit1 == 'g') {
            $rate = 0.001;
        } else if ($unit1 == 'lbs' || $unit1 == 'lb') {
            $rate = 0.453592;
        } else if ($unit1 == 'oz') {
            $rate = 0.0283495;
        } else {
            throw new Exception('Weight must be in kg, g, lbs, or oz. Default is kg');
        }

        return $rate;
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Get the rate that will convert the given dimension unit to MyFatoorah default dimension unit.
     * 
     * @param string        $unit It is the dimension unit used in width, hight, or depth. Dimension must be in cm, m, mm, in, or yd. Default is cm.
     * @return real         The conversion rate that will convert the given unit into the cm.
     * @throws Exception    Throw exception if the input unit is not support. Dimension must be in cm, m, mm, in, or yd. Default is cm.
     */
    public function getDimensionRate($unit) {

        $unit1 = strtolower($unit);
        if ($unit1 == 'cm') {
            $rate = 1; //cm is the default
        } elseif ($unit1 == 'm') {
            $rate = 100;
        } else if ($unit1 == 'mm') {
            $rate = 0.1;
        } else if ($unit1 == 'in') {
            $rate = 2.54;
        } else if ($unit1 == 'yd') {
            $rate = 91.44;
        } else {
            throw new Exception('Dimension must be in cm, m, mm, in, or yd. Default is cm');
        }

        return $rate;
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Get the rate that will convert the given currency to the default currency of MyFatoorah portal account.
     * 
     * @param string        $currency The currency that will be converted into the currency of MyFatoorah portal account.
     * @return string       The conversion rate that will convert the given currency into the default currency of MyFatoorah portal account.
     * @throws Exception    Throw exception if the input currency is not support by MyFatoorah portal account.
     */
    public function getCurrencyRate($currency) {
        $json = $this->getCurrencyRates();
        foreach (($json) as $value) {
            if ($value->Text == $currency) {
                return $value->Value;
            }
        } 
        throw new Exception('The selected currency is not supported by MyFatoorah');
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Get the rate that will convert the given currency to the default currency of MyFatoorah portal account.
     * 
     * @param string        $currency The currency that will be converted into the currency of MyFatoorah portal account.
     * @return string       The conversion rate that will convert the given currency into the default currency of MyFatoorah portal account.
     * @throws Exception    Throw exception if the input currency is not support by MyFatoorah portal account.
     */
    public function getCurrencyRates() {
        $url = "$this->apiURL/v2/GetCurrenciesExchangeList";
        return $this->callAPI($url, null, null, 'Get Currencies Exchange List');
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * 
     * @param type $totalAmount
     * @param type $currency
     * @param type $paymentCurrencyIso
     * @param type $allRatesData
     * @return type
     */
    function calcGatewayData($totalAmount, $currency, $paymentCurrencyIso, $allRatesData) {

//        if ($currency != $paymentCurrencyIso) {
        foreach ($allRatesData as $data) {
            if ($data->Text == $currency) {
                $baseCurrencyRate = $data->Value;
            }
            if ($data->Text == $paymentCurrencyIso) {
                $gatewayCurrencyRate = $data->Value;
            }
        }

        if (isset($baseCurrencyRate) && isset($gatewayCurrencyRate)) {

            $baseAmount = ceil(((int) ($totalAmount * 1000)) / $baseCurrencyRate / 10) / 100;

            return [
                'GatewayTotalAmount' => round(($baseAmount * $gatewayCurrencyRate), 3),
                'GatewayCurrency'    => $paymentCurrencyIso
            ];
        } else {
            return [
                'GatewayTotalAmount' => $totalAmount,
                'GatewayCurrency'    => $currency
            ];
        }

//        }
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Validate webhook signature function
     * 
     * @param type          $dataArray webhook request array
     * @param type          $secret webhook secret key
     * @param type          $signature MyFatoorah signature
     * @return boolean      true for valid signature
     * @throws Exception    Throw exception if signature is not valid
     */
    function validateSignature($dataArray, $secret, $signature) {
        uksort($dataArray, 'strcasecmp');

        // uksort($data, function ($a, $b) {
        //   $a = mb_strtolower($a);
        //   $b = mb_strtolower($b);
        //   return strcmp($a, $b);
        // });

        $output = implode(',', array_map(
                        function ($v, $k) {
                    return sprintf("%s=%s", $k, $v);
                },
                        $dataArray,
                        array_keys($dataArray)
        ));

//        $data      = utf8_encode($output);
//        $keySecret = utf8_encode($secret);
        // generate hash of $field string 
        $hash = base64_encode(hash_hmac('sha256', $output, $secret, true));

        if ($signature === $hash) {
            $this->log('valid signature');
            return true;
        } else {
            $this->log('Not a valid signature');
            die;
        }
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Get a list of MyFatoorah countries and their API URLs and names
     * 
     * @return array of MyFatoorah data
     */
    public static function getMyFatoorahCountries() {

        $cachedFile = dirname(__FILE__) . '/mf-config.json';

        if (file_exists($cachedFile)) {
            if ((time() - filemtime($cachedFile) > 3600)) {
                $countries = self::createNewMFConfigFile($cachedFile);
            }

            if (!empty($countries)) {
                return $countries;
            }

            $cache = file_get_contents($cachedFile);
            return ($cache) ? json_decode($cache, true) : [];
        } else {
            return self::createNewMFConfigFile($cachedFile);
        }
    }

//-----------------------------------------------------------------------------------------------------------------------------------------
    static function createNewMFConfigFile($cachedFile) {

        $curl = curl_init('https://portal.myfatoorah.com/Files/API/mf-config.json');
        curl_setopt_array($curl, array(
            CURLOPT_HTTPHEADER     => array('Content-Type: application/json'),
            CURLOPT_RETURNTRANSFER => true,
        ));

        $response  = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($http_code == 200) {
            file_put_contents($cachedFile, $response);

            return json_decode($response, true);
        }
        return [];
    }

//-----------------------------------------------------------------------------------------------------------------------------------------
}




/**
 *  PaymentMyfatoorahApiV2 handle the payment process of MyFatoorah API endpoints
 * 
 * @author MyFatoorah <tech@myfatoorah.com>
 * @copyright 2021 MyFatoorah, All rights reserved
 * @license GNU General Public License v3.0
 */
class PaymentMyfatoorahApiV2 extends MyfatoorahApiV2 {

    /**
     * To specify either the payment will be onsite or offsite
     * (default value: false)
     * 
     * @var boolean 
     */
    protected $isDirectPayment = false;

    /**
     * 
     * @var string
     */
    public static $pmCachedFile = __DIR__ . '/mf-methods.json';

    /**
     * 
     * @var array
     */
    private static $paymentMethods;

//---------------------------------------------------------------------------------------------------------------------------------------------------    

    /**
     * List available Payment Gateways.
     * 
     * @param real $invoiceValue
     * @param string $displayCurrencyIso
     * @return array of payment methods
     */
    public function getVendorGateways($invoiceValue = 0, $displayCurrencyIso = '', $isCached = false) {

        $postFields = [
            'InvoiceAmount' => $invoiceValue,
            'CurrencyIso'   => $displayCurrencyIso,
        ];

        $json = $this->callAPI("$this->apiURL/v2/InitiatePayment", $postFields, null, 'Initiate Payment');

        $paymentMethods = isset($json->Data->PaymentMethods) ? $json->Data->PaymentMethods : [];

        if (!empty($paymentMethods) && $isCached) {
            file_put_contents(self::$pmCachedFile, json_encode($paymentMethods));
        }
        return $paymentMethods;
    }

//---------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * List available Cached Payment Gateways.
     * 
     * @return array of Cached payment methods
     */
    public function getCachedVendorGateways() {

        try {
            if (file_exists(self::$pmCachedFile)) {
                $cache = file_get_contents(self::$pmCachedFile);
                return ($cache) ? json_decode($cache) : [];
            } else {
                return $this->getVendorGateways(0, '', true);
            }
        } catch (Exception $ex) {
            //do nothing
        }
        return [];
    }

//---------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * List available Payment Gateways by type (direct, cards)
     * 
     * @param bool $isDirect
     * @return array
     */
    public function getVendorGatewaysByType($isDirect = false) {
        $gateways = $this->getCachedVendorGateways();

//        try {
//            $gateways = $this->getVendorGateways();
//        } catch (Exception $ex) {
//            return [];
//        }

        $paymentMethods = [
            'cards'  => [],
            'direct' => [],
        ];

        foreach ($gateways as $g) {
            if ($g->IsDirectPayment) {
                $paymentMethods['direct'][] = $g;
            } else if ($g->PaymentMethodCode != 'ap') {
                $paymentMethods['cards'][] = $g;
            } else if ($this->isAppleSystem()) {
                //add apple payment for IOS systems
                $paymentMethods['cards'][] = $g;
            }
        }

        return ($isDirect) ? $paymentMethods['direct'] : $paymentMethods['cards'];
    }

//---------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * List available cached  Payment Methods
     * 
     * @return array
     */
    public function getCachedPaymentMethods() {
        $gateways       = $this->getCachedVendorGateways();
        $paymentMethods = ['all' => [], 'cards' => [], 'form' => []];
        foreach ($gateways as $g) {
            if ($g->IsEmbeddedSupported) {
                $paymentMethods['form'][] = $g;
                $paymentMethods['all'][]  = $g;
            } else if ($g->PaymentMethodCode != 'ap' && !$g->IsDirectPayment) {
                $paymentMethods['cards'][] = $g;
                $paymentMethods['all'][]   = $g;
            } else if ($this->isAppleSystem()) {
                $paymentMethods['cards'][] = $g;
                $paymentMethods['all'][]   = $g;
            }
        }
        return $paymentMethods;
    }

//---------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * List available Payment Methods
     * 
     * @param real $invoiceValue
     * @param string $displayCurrencyIso
     * @return array
     */
    public function getPaymentMethodsForDisplay($invoiceValue, $displayCurrencyIso) {

        if (!empty(self::$paymentMethods)) {
            return self::$paymentMethods;
        }

        $gateways = $this->getVendorGateways($invoiceValue, $displayCurrencyIso);
        $allRates = $this->getCurrencyRates();

        self::$paymentMethods = ['all' => [], 'cards' => [], 'form' => []];

        foreach ($gateways as $g) {
            $g->GatewayData = $this->calcGatewayData($g->TotalAmount, $g->CurrencyIso, $g->PaymentCurrencyIso, $allRates);
            if ($g->IsEmbeddedSupported) {
                self::$paymentMethods['form'][] = $g;
                self::$paymentMethods['all'][]  = $g;
            } else if ($g->PaymentMethodCode != 'ap' && !$g->IsDirectPayment) {
                self::$paymentMethods['cards'][] = $g;
                self::$paymentMethods['all'][]   = $g;
            } else if ($this->isAppleSystem()) { 
                //add apple payment for IOS systems
                self::$paymentMethods['cards'][] = $g;
                self::$paymentMethods['all'][]   = $g;
            }
        }
        
        return self::$paymentMethods;
    }

//---------------------------------------------------------------------------------------------------------------------------------------------------
    private function isAppleSystem() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'];

        if (stripos($userAgent, 'iPod') || stripos($userAgent, 'iPhone') || stripos($userAgent, 'iPad')) {
            return true;
        }

        $browsers = ['Opera', 'Edg', 'Chrome', 'Safari', 'Firefox', 'MSIE', 'Trident'];

        $userBrowser = null;
        foreach ($browsers as $b) {
            if (strpos($userAgent, $b) !== false) {
                $userBrowser = $b;
                break;
            }
        }
        if (!$userBrowser || $userBrowser == 'Safari') {
            return true;
        }

        return false;
    }

//---------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Get Payment Method Object
     * 
     * @param string $gateway
     * @param string $gatewayType ['PaymentMethodId', 'PaymentMethodCode']
     * @param real $invoiceValue
     * @param string $displayCurrencyIso
     * @return object
     * @throws Exception
     */
    public function getPaymentMethod($gateway, $gatewayType = 'PaymentMethodId', $invoiceValue = 0, $displayCurrencyIso = '') {

        $paymentMethods = $this->getVendorGateways($invoiceValue, $displayCurrencyIso);

        foreach ($paymentMethods as $method) {
            if ($method->$gatewayType == $gateway) {
                $pm = $method;
                break;
            }
        }

        if (!isset($pm)) {
            throw new Exception('Please contact Account Manager to enable the used payment method in your account');
        }

        if ($this->isDirectPayment && !$pm->IsDirectPayment) {
            throw new Exception($pm->PaymentMethodEn . ' Direct Payment Method is not activated. Kindly, contact your MyFatoorah account manager or sales representative to activate it.');
        }

        return $pm;
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Get the invoice/payment URL and the invoice id
     * 
     * @param array $curlData
     * @param string $gatewayId (default value: 'myfatoorah')
     * @param integer|string $orderId (default value: null) used in log file
     * @return array
     */
    public function getInvoiceURL($curlData, $gatewayId = 'myfatoorah', $orderId = null, $sessionId = null) {

        $this->log('----------------------------------------------------------------------------------------------------------------------------------');

        $this->isDirectPayment = false;

        if (!empty($sessionId)) {
            return $this->embeddedPayment($curlData, $sessionId, $orderId);
        } else if ($gatewayId == 'myfatoorah') {
            return $this->sendPayment($curlData, $orderId);
        } else {
            return $this->excutePayment($curlData, $gatewayId, $orderId);
        }
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * 
     * @param array $curlData
     * @param integer|string $gatewayId
     * @param integer|string $orderId (default value: null) used in log file
     * @return array
     */
    private function excutePayment($curlData, $gatewayId, $orderId = null) {

        $curlData['PaymentMethodId'] = $gatewayId;

        $json = $this->callAPI("$this->apiURL/v2/ExecutePayment", $curlData, $orderId, 'Excute Payment'); //__FUNCTION__

        return ['invoiceURL' => $json->Data->PaymentURL, 'invoiceId' => $json->Data->InvoiceId];
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * 
     * @param array $curlData
     * @param integer|string $orderId (default value: null) used in log file
     * @return array
     */
    private function sendPayment($curlData, $orderId = null) {

        $curlData['NotificationOption'] = 'Lnk';

        $json = $this->callAPI("$this->apiURL/v2/SendPayment", $curlData, $orderId, 'Send Payment');

        return ['invoiceURL' => $json->Data->InvoiceURL, 'invoiceId' => $json->Data->InvoiceId];
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Get the direct payment URL and the invoice id
     * 
     * @param array $curlData
     * @param integer|string $gateway
     * @param array $cardInfo
     * @param integer|string $orderId (default value: null) used in log file
     * @return array
     */
    public function directPayment($curlData, $gateway, $cardInfo, $orderId = null) {

        $this->log('----------------------------------------------------------------------------------------------------------------------------------');

        $this->isDirectPayment = true;

        $data = $this->excutePayment($curlData, $gateway, $orderId);

        $json = $this->callAPI($data['invoiceURL'], $cardInfo, $orderId, 'Direct Payment'); //__FUNCTION__
        return ['invoiceURL' => $json->Data->PaymentURL, 'invoiceId' => $data['invoiceId']];
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Get the Payment Transaction Status
     * 
     * @param string $keyId
     * @param string $KeyType
     * @param integer|string $orderId (default value: null)
     * @param string $price
     * @param string $currncy
     * @return object
     * @throws Exception
     */
    public function getPaymentStatus($keyId, $KeyType, $orderId = null, $price = null, $currncy = null) {

        //payment inquiry
        $curlData = ['Key' => $keyId, 'KeyType' => $KeyType];
        $json     = $this->callAPI("$this->apiURL/v2/GetPaymentStatus", $curlData, $orderId, 'Get Payment Status');

        $msgLog = 'Order #' . $json->Data->CustomerReference . ' ----- Get Payment Status';

        //check for the order information
        if (!$this->checkOrderInformation($json, $orderId, $price, $currncy)) {
            $err = 'Trying to call data of another order';
            $this->log("$msgLog - Exception is $err");
            throw new Exception($err);
        }


        //check invoice status (Paid and Not Paid Cases)
        if ($json->Data->InvoiceStatus == 'Paid' || $json->Data->InvoiceStatus == 'DuplicatePayment') {

            $json->Data = $this->getSuccessData($json);
            $this->log("$msgLog - Status is Paid");
        } else if ($json->Data->InvoiceStatus != 'Paid') {

            $json->Data = $this->getErrorData($json, $keyId, $KeyType);
            $this->log("$msgLog - Status is " . $json->Data->InvoiceStatus . '. Error is ' . $json->Data->InvoiceError);
        }

        return $json->Data;
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * 
     * @param object $json
     * @param string $orderId
     * @param string $price
     * @param string $currncy
     * @return boolean
     */
    private function checkOrderInformation($json, $orderId = null, $price = null, $currncy = null) {
        //check for the order ID
        if ($orderId && $json->Data->CustomerReference != $orderId) {
            return false;
        }

        //check for the order price and currency
        $invoiceDisplayValue = explode(' ', $json->Data->InvoiceDisplayValue);
        if ($price && $invoiceDisplayValue[0] != $price) {
            return false;
        }
        if ($currncy && $invoiceDisplayValue[1] != $currncy) {
            return false;
        }

        return true;
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * 
     * @param object $json
     * @return object
     */
    private function getSuccessData($json) {
        foreach ($json->Data->InvoiceTransactions as $transaction) {
            if ($transaction->TransactionStatus == 'Succss') {
                $json->Data->InvoiceStatus = 'Paid';
                $json->Data->InvoiceError  = '';

                $json->Data->focusTransaction = $transaction;
                return $json->Data;
            }
        }
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * 
     * @param object $json
     * @param string $keyId
     * @param string $KeyType
     * @return object
     */
    private function getErrorData($json, $keyId, $KeyType) {

        //------------------
        //case 1: payment is Failed
        $focusTransaction = $this->{"getLastTransactionOf$KeyType"}($json, $keyId);
        if ($focusTransaction && $focusTransaction->TransactionStatus == 'Failed') {
            $json->Data->InvoiceStatus = 'Failed';
            $json->Data->InvoiceError  = $focusTransaction->Error . '.';

            $json->Data->focusTransaction = $focusTransaction;

            return $json->Data;
        }

        //------------------
        //case 2: payment is Expired
        //all myfatoorah gateway is set to Asia/Kuwait
        $ExpiryDateTime = $json->Data->ExpiryDate . ' ' . $json->Data->ExpiryTime;
        $ExpiryDate     = new \DateTime($ExpiryDateTime, new \DateTimeZone('Asia/Kuwait'));
        $currentDate    = new \DateTime('now', new \DateTimeZone('Asia/Kuwait'));

        if ($ExpiryDate < $currentDate) {
            $json->Data->InvoiceStatus = 'Expired';
            $json->Data->InvoiceError  = 'Invoice is expired since ' . $json->Data->ExpiryDate . '.';

            return $json->Data;
        }

        //------------------
        //case 3: payment is Pending
        //payment is pending .. user has not paid yet and the invoice is not expired
        $json->Data->InvoiceStatus = 'Pending';
        $json->Data->InvoiceError  = 'Pending Payment.';

        return $json->Data;
    }

//-----------------------------------------------------------------------------------------------------------------------------------------
    function getLastTransactionOfPaymentId($json, $keyId) {
        foreach ($json->Data->InvoiceTransactions as $transaction) {
            if ($transaction->PaymentId == $keyId && $transaction->Error) {
                return $transaction;
            }
        }
    }

    function getLastTransactionOfInvoiceId($json) {
        usort($json->Data->InvoiceTransactions, function ($a, $b) {
            return strtotime($a->TransactionDate) - strtotime($b->TransactionDate);
        });

        return end($json->Data->InvoiceTransactions);
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Refund a given Payment
     * 
     * @param integer|string $paymentId
     * @param real|string $amount
     * @param string $currencyCode
     * @param string $reason
     * @param integer|string $orderId (default value: null)
     * @return object
     */
    public function refund($paymentId, $amount, $currencyCode, $reason, $orderId = null) {

        $rate = $this->getCurrencyRate($currencyCode);
        $url  = "$this->apiURL/v2/MakeRefund";

        $postFields = array(
            'KeyType'                 => 'PaymentId',
            'Key'                     => $paymentId,
            'RefundChargeOnCustomer'  => false,
            'ServiceChargeOnCustomer' => false,
            'Amount'                  => $amount / $rate,
            'Comment'                 => $reason,
        );

        return $this->callAPI($url, $postFields, $orderId, 'Make Refund');
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * 
     * @param array $curlData
     * @param integer|string $sessionId
     * @param integer|string $orderId (default value: null) used in log file
     * @return array
     */
    public function embeddedPayment($curlData, $sessionId, $orderId = null) {

        $curlData['SessionId'] = $sessionId;

        $json = $this->callAPI("$this->apiURL/v2/ExecutePayment", $curlData, $orderId, 'Excute Payment'); //__FUNCTION__

        return ['invoiceURL' => $json->Data->PaymentURL, 'invoiceId' => $json->Data->InvoiceId];
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * 
     * @param integer|string $orderId (default value: null) used in log file
     * @return array
     */
    public function getEmbeddedSession($userDefinedField = '', $orderId = null) {

        $customerIdentifier = array("CustomerIdentifier" => $userDefinedField);

        $json = $this->callAPI("$this->apiURL/v2/InitiateSession", $customerIdentifier, $orderId, 'Initiate Session'); //__FUNCTION__
        return $json->Data;
    }

//-----------------------------------------------------------------------------------------------------------------------------------------
}




/**
 * ShippingMyfatoorahApiV2 handle the shipping process of MyFatoorah API endpoints
 * 
 * @author MyFatoorah <tech@myfatoorah.com>
 * @copyright 2021 MyFatoorah, All rights reserved
 * @license GNU General Public License v3.0
 */
class ShippingMyfatoorahApiV2 extends MyfatoorahApiV2 {
//-----------------------------------------------------------------------------------------------------------------------------------------    

    /**
     * Get MyFatoorah Shipping Countries
     * 
     * @return object
     */
    function getShippingCountries() {
        $url  = "$this->apiURL/v2/GetCountries";
        $json = $this->callAPI($url, null, null, 'Get Countries');
        return $json;
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Get Shipping Cities
     * 
     * @param integer $method       [1 for DHL, 2 for Aramex]
     * @param string $countryCode   It can be obtained from getShippingCountries function
     * @param string $searchValue   The key word that will be used in searching
     * @return object
     */
    function getShippingCities($method, $countryCode, $searchValue = null) {
        $endPoint = 'GetCities?shippingMethod=' . $method . '&countryCode=' . $countryCode . '&searchValue=' . urlencode(substr($searchValue, 0, 30));
        $url      = "$this->apiURL/v2/" . $endPoint;
        $json     = $this->callAPI($url, null, null, 'Get Cities - Country : ' . $countryCode);
//        return array_map('strtolower', $json->Data->CityNames);
        return $json;
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Calculate Shipping Charge
     * 
     * @param array $curlData
     * @return object
     */
    function calculateShippingCharge($curlData) {
        $url  = "$this->apiURL/v2/CalculateShippingCharge";
        $json = $this->callAPI($url, $curlData, null, 'Calculate Shipping Charge');
        return $json;
    }

//-----------------------------------------------------------------------------------------------------------------------------------------
}
