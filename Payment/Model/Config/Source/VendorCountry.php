<?php

namespace MyFatoorah\Payment\Model\Config\Source;

use MyFatoorah\Library\MyfatoorahApiV2;
use Magento\Framework\Locale\Resolver;

class VendorCountry implements \Magento\Framework\Option\ArrayInterface {
//---------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * @var Resolver
     */
    private $localeResolver;

//---------------------------------------------------------------------------------------------------------------------------------------------------
    public function __construct(
            Resolver $localeResolver
    ) {
        $this->localeResolver = $localeResolver;
    }

//---------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * {@inheritdoc}
     */
    public function toOptionArray() {
        $options = [];

        $countries = MyfatoorahApiV2::getMyFatoorahCountries();
        $nameIndex = 'countryName' . ucfirst($this->getCurrentLocale());
        foreach ($countries as $key => $obj) {
            $options[] = ['value' => $key, 'label' => $obj[$nameIndex]];
        }

        return $options;
    }

//---------------------------------------------------------------------------------------------------------------------------------------------------
    private function getCurrentLocale() {
        $currentLocaleCode = $this->localeResolver->getLocale(); // fr_CA
        $languageCode      = strstr($currentLocaleCode, '_', true);
        return $languageCode;
    }

//---------------------------------------------------------------------------------------------------------------------------------------------------    
}
