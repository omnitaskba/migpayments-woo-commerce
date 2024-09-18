<?php
 
use chillerlan\QRCode\QRCode;
require_once 'MigpaymentsLibrary.php';
require_once 'MigpaymentsResponse.php';

 
class MigpaymentsService
{
    public static function getPaymentData($total, $currencyCode, $fiatCurrencyCode,
                                        $orderNumber, $token, $isSandbox = false, $orderData = [], $blockchainCode = null){
        $error = null;
        $data = null;
        $log = null;
        $migpaymentsLibrary = MigpaymentsLibrary::create($isSandbox);
        
        if(function_exists('wc_get_log')){
            $log = wc_get_logger();
        }
        //now the logic
        try{
            $response  = $migpaymentsLibrary->getPaymentData($total, $currencyCode, $fiatCurrencyCode, $orderNumber, $token, $orderData, $blockchainCode);
            
            if(!$response['success']){
        
                if($log){
                
                    $log->info('Failed to get payment data.' . json_encode($response), ['source' => 'migpayments']);
                } else {
                    error_log(json_encode($response));
                }
                
            }
            if(isset($response['data']) && !empty($response['data'])){
                $data = $response['data'];
                if(array_key_exists('instructions', $data)){
                    unset($data['instructions']);
                }
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
    
        return new MigpaymentsResponse($error, $data);
    }

    public static function getPaymentDataHtml($total, $currencyCode, $fiatCurrencyCode, $orderNumber, $token, $isSandbox = false, $orderData = [], $blockchainCode = null){
        $error = null;
        $data = null;
       

        try{
            $response = self::getPaymentData($total, $currencyCode, $fiatCurrencyCode, $orderNumber, $token, $isSandbox, $orderData, $blockchainCode);
            
            if(function_exists('wc_get_logger')){
                $log = wc_get_logger();
                $log->info('Get payment data response:'. json_encode($response), ['source' => 'migpayments']);
            }

            $migpayments = new WcMigpaymentsGateway();
            
            if(!$response->error && isset($response->data['cryptoAddress'])){
                $html = self::generatePaymentDataHtml(
                    $migpayments,
                    $response->data['calculatedAmount'],
                    $response->data['currency'],
                    $response->data['cryptoAddress'],
                    ucfirst(strtolower($response->data['block_chain_code'])),
                    strtotime('+' . $response->data['expiry_in_hours'] . ' hours')
                );
                
        } else {
            $html= '<div class="woocommerce-error">' . $migpayments->paymentDataErrorTxt . ' </div>';
        }

        $html .= '</div>';
        $response->data['html'] = $html;
        $data = $response->data;
        

        }catch(Exception $e){
            $error = 'Failed  to get payment data html.';
        }
    
        return new MigpaymentsResponse($error, $data);
    }

    public static function generatePaymentDataHtml
    (
        $paymentGateway,
        $amount,
        $currencyCode,
        $cryptoAddress,
        $blockchain,
        $expiresAt
        )
    {
        try{
            $addressLbl = $paymentGateway->cryptoAddressLabelTxt;
            $amountLbl = $paymentGateway->cryptoAmountLabelTxt;
            $qrCode =  (new QRCode())->render($cryptoAddress);

            $html = '<h5 style="text-align: left;">Send Payment</h5> <div id="wc-migpayments-payment-form"> <div class="wc-migpayments-payment-data">  ';
            $html .= '<img id="wc-migpayments-address-qr-code"  src="'. $qrCode .'" alt="QR Code" /> ';
            $html .= '<div class="wc-migpayments-payment-items">';
            $html .= '<div class="wc-migpayments-payment-item"> <div class="wc-payment-data-item"> <label for="addresss">'.  $addressLbl  . '</label> <p> ' . $cryptoAddress . ' </p> </div> <button class="wc-migpayments-copy-btn" onclick="copyToClipboard(\''.  $cryptoAddress .'\')">Copy</button> </div> <div class="wc-migpayments-payment-item"> <div class="wc-payment-data-item" > <label for="total_crypto_amount">' . $amountLbl.'</label> <p id="total_crypto_amount" data-amount="'. $amount .'">  ' . $amount . ' ' . $currencyCode .' </p> </div> <button class="wc-migpayments-copy-btn"  onclick="copyToClipboard('. $amount. ')">Copy</button> </div>';
            $html .= '<div class="wc-migpayments-payment-item"> <div class="wc-payment-data-item"> <label for="network">Use this exact network:</label> <p> ' . $blockchain . ' </p> </div> </div>';
            $html .= '<div class="wc-migpayments-payment-item" id="wc-payment-expiry-item"> <div class="wc-payment-data-item" > <label for="expires_at">Expires In:</label> <div id="wc-migpayments-expiry-countdown" data-target-date="'. $expiresAt .'"></div> </div>';
            $html .= '</div></div></div>';
 
        } catch(Exception $e){
            $html =  '<div class="woocommerce-error">' . $paymentGateway->paymentDataErrorTxt . ' </div>';

        }
        return $html;
    }

    public static function getCryptoPrices($total, $cryptoCurrencies, $fiatCurrencyCode, $token, $isSandbox = false){
        $error = null;
        $data = null;
        $migpaymentsLibrary = MigpaymentsLibrary::create($isSandbox);
    
        //now the logic
        try{
            $response  = $migpaymentsLibrary->getCryptoPrices($total, $cryptoCurrencies, $fiatCurrencyCode, $token);
                
            if(!$response['success']){
                $error = 'Failed to get crypto estimate.';
                return new MigpaymentsResponse($error);
            }
        
            if(isset($response['data']) && !empty($response['data']))
            {
                $data = $response['data'];
            }
        
        } catch (Exception $e){
            $error = 'Failed  to get crypto estimate.';
        }
        return new MigpaymentsResponse($error, $data);
    }

    public static function getCryptoPricesHtml($total, $cryptoCurrencies, $fiatCurrencyCode, $token, $isSandbox = false){
        $error = null;
        $data = null;

        if(function_exists('wc_get_logger')){
            $log = wc_get_logger();
        }

        try{
            $html = '<div id="wc-migpayments-crypto-estimate-wrapper">';
            $response = self::getCryptoPrices($total, $cryptoCurrencies, $fiatCurrencyCode, $token, $isSandbox);

            if($log){
                $log->info('Get crypto estimate for '. $total . ' '. $fiatCurrencyCode .' response: '. json_encode($response), ['source' => 'migpayments']);
            }

            if(!$response->error && isset($response->data['prices'])){
            
                foreach($response->data['prices'] as $currencyCode => $price){
                    switch($currencyCode){
                        case 'ETH':
                            $currencyName = 'Ethereum';
                            $network = 'ERC-20 Network';
                        break;
                        case 'BTC':
                            $currencyName = 'Bitcoin';
                            $network = 'BTC Network';
                        break;
                        case 'USDT':
                            $currencyName = 'USD Tether';
                            $network = 'ERC-20 Network';
                        break;
                        case 'USDC':
                            $currencyName = 'USD Coin';
                            $network = 'ERC-20 Network';
                        break;
                        default:
                        $currencyName = '';
                        break;
                    }
                $html .=  '<div class="wc-migpayments-crypto-estimate-item"> <div class="wc-migpayments-estimate-currency"> '. $currencyName . '<span class="wc-migpayments-estimate-symbol"> (' . $currencyCode. ') <b><small>(' . $network. ')</small></b></span></div> <div class="wc-migpayments-estimate-currency"> ' . $price .'</div>  </div>';
                }
            } else {
                $html .= '<p><small>Failed to fetch crypto estimate.</small></p>';
 
            }

            $html .= '</div>';
            $data = $html;
        }catch(Exception $e){
            if($log){
                $log->info('Failed to get crypto estimate.' . json_encode($e), ['source' => 'migpayments']);
            } else {
                error_log(json_encode($e));
            }
            $error = 'Failed  to get crypto estimate.';
        }
        
        return new MigpaymentsResponse($error, $data);
    }

    public static function getCurrencyBlockchains($currencyCode, $isSandbox)
    {
    
        $error = null;
        $data = null;
        $migpaymentsLibrary = MigpaymentsLibrary::create($isSandbox);
        $errorMsg = 'Failed to get currency blockchains';
        
        try{
            $response  = $migpaymentsLibrary->getCurrencyBlockchains($currencyCode);
                
            if(!$response['success']){
                $error = $errorMsg;
                return new MigpaymentsResponse($error);
            }
        
            if(isset($response['data']) && !empty($response['data']))
            {
                $data = $response['data']['block_chains'];
            }
        
        } catch (Exception $e){
            $error = $errorMsg;
        }
        return new MigpaymentsResponse($error, $data);
    }

 
}
