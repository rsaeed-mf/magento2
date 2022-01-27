<?php

namespace MyFatoorah\Payment\Gateway\Config;

use MyFatoorah\Library\PaymentMyfatoorahApiV2;

/**
 * Class Config.
 * Values returned from Magento\Payment\Gateway\Config\Config.getValue()
 * are taken by default from ScopeInterface::SCOPE_STORE
 */
class Config extends \Magento\Payment\Gateway\Config\Config {

    const CODE                                 = 'myfatoorah_payment';
    const PLUGIN_VERSION                       = '2.0.0.0';
    const KEY_ACTIVE                           = 'active';
    const KEY_API_KEY                          = 'api_key';
    const KEY_GATEWAYS                         = 'list_options';
    const KEY_Title                            = 'title';
    const KEY_DEBUG                            = 'debug';
    const KEY_MYFATOORAH_APPROVED_ORDER_STATUS = 'myfatoorah_approved_order_status';
    const KEY_EMAIL_CUSTOMER                   = 'email_customer';
    const KEY_AUTOMATIC_INVOICE                = 'automatic_invoice';
    const KEY_IS_TESTING                       = 'is_testing';
    const KEY_COUNTRY_MODE                     = 'countryMode';
    const KEY_LAUNCH_TIME                      = 'launch_time';
    const KEY_LAUNCH_TIME_UPDATED              = 'launch_time_updated';
    const KEY_TOKENIZATION                     = 'save_card';

    /**
     * Get Launch Time
     *
     * @return string
     */
    public function getLaunchTime() {
        return $this->getValue(self::KEY_LAUNCH_TIME);
    }

    /**
     * Get Launch Time Updated
     *
     * @return string
     */
    public function getLaunchTimeUpdated() {
        return $this->getValue(self::KEY_LAUNCH_TIME_UPDATED);
    }

    /**
     * Get Title
     *
     * @return string
     */
    public function getTitle() {
        return $this->getValue(self::KEY_Title);
    }

    /**
     * Get Logo
     *
     * @return string
     */
    public function getLogo() {

        $gateways = $this->getKeyGateways();
        return (substr_count($gateways, ',') == 0) ? "$gateways.png" : 'myfatoorah.png';
    }

    /**
     * Get Description
     *
     * @return string
     */
    public function getDescription() {
        return '';
    }

    /**
     * Get Gateway URL
     *
     * @return string
     */

    /**
     * get the myfatoorah refund gateway Url
     * @return string
     */
    public function getRefundUrl() {
        return 'https://' . ( $this->isTesting() ? 'apitest.myfatoorah.com/v2/MakeRefund' : 'api.myfatoorah.com/v2/MakeRefund' );
    }

    /**
     * Get API Key
     *
     * @return string
     */
    public function getApiKey() {
        return $this->getValue(self::KEY_API_KEY);
    }

    /**
     * Get MyFatoorah Approved Order Status
     *
     * @return string
     */
    public function getMyFatoorahApprovedOrderStatus() {
        return $this->getValue(self::KEY_MYFATOORAH_APPROVED_ORDER_STATUS);
    }

    /**
     * Check if customer is to be notified
     * @return boolean
     */
    public function isEmailCustomer() {
        return (bool) $this->getValue(self::KEY_EMAIL_CUSTOMER);
    }

    /**
     * Check if customer is to be notified
     * @return boolean
     */
    public function isAutomaticInvoice() {
        return (bool) $this->getValue(self::KEY_AUTOMATIC_INVOICE);
    }

    /**
     * Get Payment configuration status
     * @return bool
     */
    public function isActive() {
        return (bool) $this->getValue(self::KEY_ACTIVE);
    }

    /**
     * Get if doing test transactions (request send to sandbox gateway)
     *
     * @return boolean
     */
    public function isTesting() {
        return (bool) $this->getValue(self::KEY_IS_TESTING);
    }

    /**
     * Get if doing test transactions (request send to sandbox gateway)
     *
     * @return boolean
     */
    public function getCounrtyMode() {
        return $this->getValue(self::KEY_COUNTRY_MODE);
    }

    /**
     * Get the version number of this plugin itself
     *
     * @return string
     */
    public function getVersion() {
        return self::PLUGIN_VERSION;
    }

    public function getCode() {
        return self::CODE;
    }

    /**
     * Get Key gateways
     *
     * @return string
     */
    public function getKeyGateways() {
        return $this->getValue(self::KEY_GATEWAYS);
    }

    /**
     * Get Save Card
     *
     * @return string
     */
    public function getSaveCard() {
        return $this->getValue(self::KEY_TOKENIZATION);
    }

    /**
     * Get API Key
     *
     * @return string
     */
    public function getMyfatoorahObject() {
        $log = new \Zend\Log\Logger();
        $log->addWriter(new \Zend\Log\Writer\Stream(BP . '/var/log/myfatoorah.log'));

        $apiKey      = $this->getApiKey();
        $isTesting   = $this->isTesting();
        $countryMode = $this->getCounrtyMode();

        return new PaymentMyfatoorahApiV2($apiKey, $countryMode, $isTesting, $log, 'info');
    }

    /**
     * Get Embedded App Session Id
     *
     * @return string
     */
    public function getSession() {
        $myfatoorah = $this->getMyfatoorahObject();
        return $myfatoorah->getEmbeddedSession();
    }

}
