<?php namespace MyFatoorah\Gateway\Library; use MyFatoorah\Gateway\Library\MyfatoorahApiV2; use Exception; class  PaymentMyfatoorahApiV2 extends MyfatoorahApiV2{protected $isDirectPayment=false;public static $pmCachedFile=__DIR__.'/mf-methods.json';private static $paymentMethods;public function getVendorGateways($invoiceValue=0,$displayCurrencyIso='',$isCached=false){$postFields=['InvoiceAmount'=>$invoiceValue,'CurrencyIso'=>$displayCurrencyIso,];$json=$this->callAPI("$this->apiURL/v2/InitiatePayment",$postFields,null,'Initiate Payment');$paymentMethods=isset($json->Data->PaymentMethods)?$json->Data->PaymentMethods:[];if(!empty($paymentMethods)&&$isCached){file_put_contents(self::$pmCachedFile,json_encode($paymentMethods));}return $paymentMethods;}public function getCachedVendorGateways(){try{if(file_exists(self::$pmCachedFile)){$cache=file_get_contents(self::$pmCachedFile);return($cache)?json_decode($cache):[];}else{return $this->getVendorGateways(0,'',true);}}catch(Exception $ex){}return[];}public function getVendorGatewaysByType($isDirect=false){$gateways=$this->getCachedVendorGateways();$paymentMethods=['cards'=>[],'direct'=>[],];foreach($gateways as $g){if($g->IsDirectPayment){$paymentMethods['direct'][]=$g;}else if($g->PaymentMethodCode!='ap'){$paymentMethods['cards'][]=$g;}else if($this->isAppleSystem()){$paymentMethods['cards'][]=$g;}}return($isDirect)?$paymentMethods['direct']:$paymentMethods['cards'];}public function getCachedPaymentMethods(){$gateways=$this->getCachedVendorGateways();$paymentMethods=['all'=>[],'cards'=>[],'form'=>[]];foreach($gateways as $g){if($g->IsEmbeddedSupported){$paymentMethods['form'][]=$g;$paymentMethods['all'][]=$g;}else if($g->PaymentMethodCode!='ap'&&!$g->IsDirectPayment){$paymentMethods['cards'][]=$g;$paymentMethods['all'][]=$g;}else if($this->isAppleSystem()){$paymentMethods['cards'][]=$g;$paymentMethods['all'][]=$g;}}return $paymentMethods;}public function getPaymentMethodsForDisplay($invoiceValue,$displayCurrencyIso){if(!empty(self::$paymentMethods)){return self::$paymentMethods;}$gateways=$this->getVendorGateways($invoiceValue,$displayCurrencyIso);$allRates=$this->getCurrencyRates();self::$paymentMethods=['all'=>[],'cards'=>[],'form'=>[]];foreach($gateways as $g){$g->GatewayData=$this->calcGatewayData($g->TotalAmount,$g->CurrencyIso,$g->PaymentCurrencyIso,$allRates);if($g->IsEmbeddedSupported){self::$paymentMethods['form'][]=$g;self::$paymentMethods['all'][]=$g;}else if($g->PaymentMethodCode!='ap'&&!$g->IsDirectPayment){self::$paymentMethods['cards'][]=$g;self::$paymentMethods['all'][]=$g;}else if($this->isAppleSystem()){self::$paymentMethods['cards'][]=$g;self::$paymentMethods['all'][]=$g;}}return self::$paymentMethods;}private function isAppleSystem(){$userAgent=$_SERVER['HTTP_USER_AGENT'];if(stripos($userAgent,'iPod')||stripos($userAgent,'iPhone')||stripos($userAgent,'iPad')){return true;}$browsers=['Opera','Edg','Chrome','Safari','Firefox','MSIE','Trident'];$userBrowser=null;foreach($browsers as $b){if(strpos($userAgent,$b)!==false){$userBrowser=$b;break;}}if(!$userBrowser||$userBrowser=='Safari'){return true;}return false;}public function getPaymentMethod($gateway,$gatewayType='PaymentMethodId',$invoiceValue=0,$displayCurrencyIso=''){$paymentMethods=$this->getVendorGateways($invoiceValue,$displayCurrencyIso);foreach($paymentMethods as $method){if($method->$gatewayType==$gateway){$pm=$method;break;}}if(!isset($pm)){throw new Exception('Please contact Account Manager to enable the used payment method in your account');}if($this->isDirectPayment&&!$pm->IsDirectPayment){throw new Exception($pm->PaymentMethodEn.' Direct Payment Method is not activated. Kindly, contact your MyFatoorah account manager or sales representative to activate it.');}return $pm;}public function getInvoiceURL($curlData,$gatewayId='myfatoorah',$orderId=null,$sessionId=null){$this->log('----------------------------------------------------------------------------------------------------------------------------------');$this->isDirectPayment=false;if(!empty($sessionId)){return $this->embeddedPayment($curlData,$sessionId,$orderId);}else if($gatewayId=='myfatoorah'){return $this->sendPayment($curlData,$orderId);}else{return $this->excutePayment($curlData,$gatewayId,$orderId);}}private function excutePayment($curlData,$gatewayId,$orderId=null){$curlData['PaymentMethodId']=$gatewayId;$json=$this->callAPI("$this->apiURL/v2/ExecutePayment",$curlData,$orderId,'Excute Payment');return['invoiceURL'=>$json->Data->PaymentURL,'invoiceId'=>$json->Data->InvoiceId];}private function sendPayment($curlData,$orderId=null){$curlData['NotificationOption']='Lnk';$json=$this->callAPI("$this->apiURL/v2/SendPayment",$curlData,$orderId,'Send Payment');return['invoiceURL'=>$json->Data->InvoiceURL,'invoiceId'=>$json->Data->InvoiceId];}public function directPayment($curlData,$gateway,$cardInfo,$orderId=null){$this->log('----------------------------------------------------------------------------------------------------------------------------------');$this->isDirectPayment=true;$data=$this->excutePayment($curlData,$gateway,$orderId);$json=$this->callAPI($data['invoiceURL'],$cardInfo,$orderId,'Direct Payment');return['invoiceURL'=>$json->Data->PaymentURL,'invoiceId'=>$data['invoiceId']];}public function getPaymentStatus($keyId,$KeyType,$orderId=null,$price=null,$currncy=null){$curlData=['Key'=>$keyId,'KeyType'=>$KeyType];$json=$this->callAPI("$this->apiURL/v2/GetPaymentStatus",$curlData,$orderId,'Get Payment Status');$msgLog='Order #'.$json->Data->CustomerReference.' ----- Get Payment Status';if(!$this->checkOrderInformation($json,$orderId,$price,$currncy)){$err='Trying to call data of another order';$this->log("$msgLog - Exception is $err");throw new Exception($err);}if($json->Data->InvoiceStatus=='Paid'||$json->Data->InvoiceStatus=='DuplicatePayment'){$json->Data=$this->getSuccessData($json);$this->log("$msgLog - Status is Paid");}else if($json->Data->InvoiceStatus!='Paid'){$json->Data=$this->getErrorData($json,$keyId,$KeyType);$this->log("$msgLog - Status is ".$json->Data->InvoiceStatus.'. Error is '.$json->Data->InvoiceError);}return $json->Data;}private function checkOrderInformation($json,$orderId=null,$price=null,$currncy=null){if($orderId&&$json->Data->CustomerReference!=$orderId){return false;}$invoiceDisplayValue=explode(' ',$json->Data->InvoiceDisplayValue);if($price&&$invoiceDisplayValue[0]!=$price){return false;}if($currncy&&$invoiceDisplayValue[1]!=$currncy){return false;}return true;}private function getSuccessData($json){foreach($json->Data->InvoiceTransactions as $transaction){if($transaction->TransactionStatus=='Succss'){$json->Data->InvoiceStatus='Paid';$json->Data->InvoiceError='';$json->Data->focusTransaction=$transaction;return $json->Data;}}}private function getErrorData($json,$keyId,$KeyType){$focusTransaction=$this->{"getLastTransactionOf$KeyType"}($json,$keyId);if($focusTransaction&&$focusTransaction->TransactionStatus=='Failed'){$json->Data->InvoiceStatus='Failed';$json->Data->InvoiceError=$focusTransaction->Error.'.';$json->Data->focusTransaction=$focusTransaction;return $json->Data;}$ExpiryDateTime=$json->Data->ExpiryDate.' '.$json->Data->ExpiryTime;$ExpiryDate=new \DateTime($ExpiryDateTime,new \DateTimeZone('Asia/Kuwait'));$currentDate=new \DateTime('now',new \DateTimeZone('Asia/Kuwait'));if($ExpiryDate<$currentDate){$json->Data->InvoiceStatus='Expired';$json->Data->InvoiceError='Invoice is expired since '.$json->Data->ExpiryDate.'.';return $json->Data;}$json->Data->InvoiceStatus='Pending';$json->Data->InvoiceError='Pending Payment.';return $json->Data;}function getLastTransactionOfPaymentId($json,$keyId){foreach($json->Data->InvoiceTransactions as $transaction){if($transaction->PaymentId==$keyId&&$transaction->Error){return $transaction;}}}function getLastTransactionOfInvoiceId($json){usort($json->Data->InvoiceTransactions,function($a,$b){return strtotime($a->TransactionDate)-strtotime($b->TransactionDate);});return end($json->Data->InvoiceTransactions);}public function refund($paymentId,$amount,$currencyCode,$reason,$orderId=null){$rate=$this->getCurrencyRate($currencyCode);$url="$this->apiURL/v2/MakeRefund";$postFields=array('KeyType'=>'PaymentId','Key'=>$paymentId,'RefundChargeOnCustomer'=>false,'ServiceChargeOnCustomer'=>false,'Amount'=>$amount/$rate,'Comment'=>$reason,);return $this->callAPI($url,$postFields,$orderId,'Make Refund');}public function embeddedPayment($curlData,$sessionId,$orderId=null){$curlData['SessionId']=$sessionId;$json=$this->callAPI("$this->apiURL/v2/ExecutePayment",$curlData,$orderId,'Excute Payment');return['invoiceURL'=>$json->Data->PaymentURL,'invoiceId'=>$json->Data->InvoiceId];}public function getEmbeddedSession($userDefinedField='',$orderId=null){$customerIdentifier=array("CustomerIdentifier"=>$userDefinedField);$json=$this->callAPI("$this->apiURL/v2/InitiateSession",$customerIdentifier,$orderId,'Initiate Session');return $json->Data;}}