<?php
require_once('WC_Migpayments_Library.php');

class WC_Migpayments_ServiceResponse
{
    public $data;
    public $error;

    public function __construct($error = null, $data = [])
    {
        $this->error = $error;
        $this->data = $data;
    }

}
 
class WC_Migpayments_Service
{
 
    public static function getPaymentData($total, $cryptoCurrencyCode, $fiatCurrencyCode, $orderNumber, $token){
        $error = null;
        $data = null;
        $migpaymentsLibrary = WC_Migpayments_Library::create();
    
        //now the logic
        try{
			$response  = $migpaymentsLibrary->getPaymentData($total, $cryptoCurrencyCode, $fiatCurrencyCode, $orderNumber, $token);
			$cryptoPricesHtml = '';

			if(!$response['success'])
                $error = 'Failed to get payment data.';

            $data = $response['data'];

        }catch(Exception $e){
         
            $error = 'Failed to get payment data.';
        }

        return new WC_Migpayments_ServiceResponse($error, $data);
    }

    public static function getCryptoPrices($total, $cryotoCurrencies, $fiatCurrencyCode, $token){
        $error = null;
        $data = null;
        $migpaymentsLibrary = WC_Migpayments_Library::create();
    
        //now the logic
        try{
			$response  = $migpaymentsLibrary->getCryptoPrices($total, $cryotoCurrencies, $fiatCurrencyCode, $token);
			 
			if(!$response['success'])
                $error = 'Failed to get crypto prices.';

            $data = $response['data'];

        }catch(Exception $e){
         
            $error = 'Failed  to get crypto prices.';
        }

        return new WC_Migpayments_ServiceResponse($error, $data);
    }

    public static function getCryptoPricesHtml($total, $cryotoCurrencies, $fiatCurrencyCode, $token){
        $error = null;
        $data = null;
        $html = '';
        //now the logic
        try{
			 $response = self::getCryptoPrices($total, $cryotoCurrencies, $fiatCurrencyCode, $token);
             if(!$response->error && isset($response->data['prices'])){
                 foreach($response->data['prices'] as $currencyCode => $price){
                     $html .=   $price . ' <b>' . $currencyCode. ' </b></br>';
                 }
             }
             $html .= '<hr>';
             $data = $html;
        }catch(Exception $e){
         
            $error = 'Failed  to get crypto prices html.';
        }

        return new WC_Migpayments_ServiceResponse($error, $data);
    }
 
}
