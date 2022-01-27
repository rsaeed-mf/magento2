<?php

define('MYFATOORAH_LIBRARY', __DIR__ . '/Library');

require_once MYFATOORAH_LIBRARY . '/MyfatoorahLoader.php';
require_once MYFATOORAH_LIBRARY . '/MyfatoorahLibrary.php';

\Magento\Framework\Component\ComponentRegistrar::register(
        \Magento\Framework\Component\ComponentRegistrar::MODULE,
        'MyFatoorah_Payment',
        __DIR__ . '/Payment'
);

\Magento\Framework\Component\ComponentRegistrar::register(
        \Magento\Framework\Component\ComponentRegistrar::MODULE,
        'MyFatoorah_Shipping',
        __DIR__ . '/Shipping'
);
