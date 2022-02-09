<?php

namespace MyFatoorah\Gateway\Api;

interface WebHookInterface {

    /**
     * 
     * @param integer $EventType
     * @param string $Event
     * @param string $DateTime
     * @param string $CountryIsoCode
     * @param string[] $Data
     * @return string
     */
    public function execute($EventType, $Event, $DateTime, $CountryIsoCode, $Data);
}
