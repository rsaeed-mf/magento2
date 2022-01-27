<?php

/**
 * This file is responsible for updating the MyfatoorahLibrary file everyday 
 *
 * MyFatoorah offers a seamless business experience by offering a technology put together by our tech team. This enables smooth business operations involving sales activity, product invoicing, shipping, and payment processing. MyFatoorah invoicing and payment gateway solution trigger your business to greater success at all levels in the new age world of commerce. Leverage your sales and payments at all e-commerce platforms (ERPs, CRMs, CMSs) with transparent and slick applications that are well-integrated into social media and telecom services. For every closing sale click, you make a business function gets done for you, along with generating factual reports and statistics to fine-tune your business plan with no-barrier low-cost.
 * Our technology experts have designed the best GCC E-commerce solutions for the native financial instruments (Debit Cards, Credit Cards, etc.) supporting online sales and payments, for events, shopping, mall, and associated services.
 *
 * Created by MyFatoorah http://www.myfatoorah.com/
 * Developed By tech@myfatoorah.com
 * Date: 17/01/2022
 * Time: 12:00
 *
 * API Documentation on https://myfatoorah.readme.io/docs
 * Library Documentation and Download link on https://myfatoorah.readme.io/docs/php-library
 * 
 * @author MyFatoorah <tech@myfatoorah.com>
 * @copyright 2021 MyFatoorah, All rights reserved
 * @license GNU General Public License v3.0
 */
$mfLibFile = __DIR__ . '/MyfatoorahLibrary.php';

if (!file_exists($mfLibFile) || (time() - filemtime($mfLibFile) > 86400)) {
    $curl = curl_init('https://myfatoorah.a2hosted.com/library/2.0.0.x/MyfatoorahLibrary.txt');
    curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => true,
    ));

    $response  = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    curl_close($curl);
    if ($http_code == 200) {
        $noTagStr = substr($response, 5);
        $newStr = '<?php namespace MyFatoorah\Library; use Exception;';
        
        file_put_contents($mfLibFile, $newStr . $noTagStr);
    }
}
