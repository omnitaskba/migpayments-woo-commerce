<?php 

class WC_Migpayments_Library {

    private $baseUrl = 'http://mig.test:8888/api/v1/';

    private $http;

    public function __construct($http)
    {
        $this->http = $http;
     
    }
    
    public static function create()
    {
        return new self(_wp_http_get_object());
    }
 

    public function getPaymentData($total, $currencyCode, $fiatCurrencyCode, $orderNumber, $token)
    {
        $url = $this->baseUrl . 'rest/get-payment-data';
        error_log($url);
        $data = [
            'shop_currency' => $fiatCurrencyCode,
            'selected_currency' => $currencyCode,
            'token' => $token,
            'amount' => $total,
            'payment_confirmed' => get_site_url().'/wc-api/crypto-payment-confirmed?id='. $orderNumber,
            'notification_url' => get_site_url().'/wc-api/crypto-payment-confirmed?id='. $orderNumber,
            'order_number' => $orderNumber,
            'plugin' => 'wordpress'
        ];

        $response = $this->http->post($url, ['body' => $data]);
        return $this->processResults($response);
    }

    public function getCryptoPrices($total, $currencyCodes, $fiatCurrencyCode, $token)
    {
        $url = $this->baseUrl . 'get-crypto-prices';
 
        $data = [
            'currencies' => implode(',', $currencyCodes),
            'shop_currency' => $fiatCurrencyCode,
            'token' => $token,
            'amount' => $total
        ];

        $response = $this->http->post($url, ['body' => $data]);
        return $this->processResults($response);
    }

    private function processResults($response){

        if(!is_array($response)){
            return [
                'success' => 'false',
                'status' => 500
            ];
        }

        $data  = is_array($response) &&  isset($response['body']) ? json_decode($response['body'], true) : [];

        if(isset($data['data'])){
            $data = $data['data'];
        }
        return [
            'success' => $response['response']['code'] >= 200  && $response['response']['code'] < 300 ? true :false,
            'status' => $response['response']['code'] ,
            'data' => $data
        ];
    }
}
 