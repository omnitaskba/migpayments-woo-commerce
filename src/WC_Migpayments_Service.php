<?php
require_once('WC_Migpayments_Library.php');
use chillerlan\QRCode\QRCode; 
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
 
    public static function getPaymentData($total, $cryptoCurrencyCode, $fiatCurrencyCode, $orderNumber, $token, $isSandbox = false, $orderData = []){
        $error = null;
        $data = null;
        $log = null;
        $migpaymentsLibrary = WC_Migpayments_Library::create($isSandbox);
        
        if(function_exists('wc_get_log')){
            $log = wc_get_logger();
        }
        //now the logic
        try{
			$response  = $migpaymentsLibrary->getPaymentData($total, $cryptoCurrencyCode, $fiatCurrencyCode, $orderNumber, $token, $orderData);
		 
			if(!$response['success']){
               
                if($log){
                  
                    $log->info('Failed to get payment data.' . json_encode($response), ['source' => 'migpayments']);
                } else {
                    error_log(json_encode($response));
                }
               
            }
            if(isset($response['data']) && !empty($response['data'])){
                $data = $response['data'];
            } else {
                $error = 'Failed to get payment data.';
            }
              
             

        }catch(Exception $e){
         
            if($log){
                  
                $log->info('Failed to get payment data.' . json_encode($e), ['source' => 'migpayments']);
            } else {
                error_log(json_encode($e));
            }
            $error = 'Failed to get payment data.';
        }
         
        return new WC_Migpayments_ServiceResponse($error, $data);
    }

    public static function getPaymentDataHtml($total, $cryptoCurrencyCode, $fiatCurrencyCode, $orderNumber, $token, $isSandbox = false, $orderData = []){
        $error = null;
        $data = null;
        
        try{
            $response = self::getPaymentData($total, $cryptoCurrencyCode, $fiatCurrencyCode, $orderNumber, $token, $isSandbox, $orderData);
            if(function_exists('wc_get_logger')){
                $logger = wc_get_logger();
                $logger->info('Get payment data response:'. json_encode($response));
            }
            
            if(!$response->error && isset($response->data['cryptoAddress'])){
                  
                $migpayments = new WC_Gateway_MigPayments();
               
                $addressLbl = $migpayments->cryptoAddressLabelTxt;
                $amountLbl =   $migpayments->cryptoAmountLabelTxt;
                $html = '<h5 style="text-align: left;">Send Payment</h5> <div id="wc-migpayments-payment-form"> <div class="wc-migpayments-payment-data">  ';
                $qrCode =  (new QRCode())->render($response->data['cryptoAddress']);
                
                $html .= '<img id="wc-migpayments-address-qr-code"  src="'. $qrCode .'" alt="QR Code" /> ';
                $html .= '<div class="wc-migpayments-payment-items"> <div class="wc-migpayments-payment-item"> <div class="wc-payment-data-item"> <label for="addresss">'.  $addressLbl  . '</label> <p> ' . $response->data['cryptoAddress'] . ' </p> </div> <button class="wc-migpayments-copy-btn" onclick="copyToClipboard("\''.  $response->data['cryptoAddress'] . '\')">Copy</button> </div> <div class="wc-migpayments-payment-item"> <div class="wc-payment-data-item" > <label for="total_crypto_amount">' . $amountLbl.'</label> <p id="total_crypto_amount" data-amount="'. $response->data['calculatedAmount'] .'">  ' . $response->data['calculatedAmount'] . ' ' . $response->data['currency'] .' </p> </div> <button class="wc-migpayments-copy-btn"  onclick="copyToClipboard('. "\' $response->data['cryptoAddress'] \'" . ')">Copy</button> </div> </div>';
                $html .= '</div></div>';
              } else {
          
                $html= '<div class="woocommerce-error">Failed to get payment data. Please try again later.</div>';
            }
            $html .= '</div>';
            $data = $html;
             

        }catch(Exception $e){
             
            $error = 'Failed  to get payment data html.';
           
        }
         
        return new WC_Migpayments_Response($error, $data);
    }

    public static function getCryptoPrices($total, $cryotoCurrencies, $fiatCurrencyCode, $token, $isSandbox = false){
        $error = null;
        $data = null;
        $migpaymentsLibrary = WC_Migpayments_Library::create($isSandbox);
       
        //now the logic
        try{
			$response  = $migpaymentsLibrary->getCryptoPrices($total, $cryotoCurrencies, $fiatCurrencyCode, $token);
			 
			if(!$response['success']){
                $error = 'Failed to get crypto prices.';
                 
            }
             
            if(isset($response['data']) && !empty($response['data']))
                $data = $response['data'];
             

        }catch(Exception $e){
         
            $error = 'Failed  to get crypto prices.';
        }
        
       
        return new WC_Migpayments_Response($error, $data);
    }

    public static function getCryptoPricesHtml($total, $cryotoCurrencies, $fiatCurrencyCode, $token, $isSandbox = false){
        $error = null;
        $data = null;

        if(function_exists('wc_get_logger')){
            $logger = wc_get_logger();
        }

       
        //now the logic
        try{
            $html = '<div id="wc-migpayments-crypto-estimate-wrapper">';

          
			 $response = self::getCryptoPrices($total, $cryotoCurrencies, $fiatCurrencyCode, $token, $isSandbox);
             if($logger){
                $logger->info('Get crypto prices for '. $total . ' '. $fiatCurrencyCode .' response: '. json_encode($response));
            }
             if(!$response->error && isset($response->data['prices'])){
                
                 foreach($response->data['prices'] as $currencyCode => $price){
                    switch($currencyCode){
                        case 'ETH':
                            $currencyName = 'Ethereum';
                        break;
                        case 'BTC':
                            $currencyName = 'Bitcoin';
                        break;
                        case 'USDT':
                            $currencyName = 'USD Tether';
                        break;
                        default:
                        $currencyName = '';
                        break;

                    }
                     $html .=  '<div class="wc-migpayments-crypto-estimate-item"> <div class="wc-migpayments-estimate-currency"> '. $currencyName . '<span class="wc-migpayments-estimate-symbol"> (' . $currencyCode. ')</span></div> <div class="wc-migpayments-estimate-currency"> ' . $price .'</div>  </div>';
                 }
             } else {
                $html .= '<p><small>Failed to fetch crypto estimate.</small></p>';
             }
             $html .= '</div>';
             $data = $html;
        }catch(Exception $e){
            if($logger){
                  
                $logger->info('Failed to get crypto estimate.' . json_encode($e), ['source' => 'migpayments']);
            } else {
                error_log(json_encode($e));
            }
            $error = 'Failed  to get crypto estimate.';
        }
        
        return new WC_Migpayments_Response($error, $data);
    }

   
 
}
