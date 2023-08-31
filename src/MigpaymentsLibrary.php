<?php

class MigpaymentsLibrary {

    private $sandboxUrl = 'http://mig.test/api/v1/';
    private $baseUrl = 'https://migpayments.tech/api/v1/';

    private $http;
    private $isSandbox;

    public function __construct($http, $isSandbox = false)
    {
        $this->http = $http;
        $this->isSandbox = $isSandbox;
     
    }
    
    public static function create($isSandbox = false)
    {
        return new self(new WP_Http(), $isSandbox);
    }
 

    public function getPaymentData($total, $currencyCode, $fiatCurrencyCode, $orderNumber, $token, $orderData = [])
    {
        $method = 'rest/get-payment-data';
        $url = $this->getFullUrl($method);
        $siteUrl = get_site_url();
        
        $data = [
            'shop_currency' => $fiatCurrencyCode,
            'selected_currency' => $currencyCode,
            'token' => $token,
            'amount' => $total,
            'payment_confirmed' => $siteUrl . '/wc-api/crypto-payment-confirmed?id='. $orderNumber,
            'notification_url' => $siteUrl . '/wc-api/crypto-payment-confirmed?id='. $orderNumber,
            'partial_payment_url' => $siteUrl . '/wc-api/crypto-partial-payment?id='. $orderNumber,
            'overpaid_payment_url' => $siteUrl . '/wc-api/crypto-overpaid-payment?id='. $orderNumber,
            'order_number' => $orderNumber,
            'plugin' => 'wordpress',
            'order_data' => $orderData
        ];

        $response = $this->http->post($url, ['body' => $data]);
        return $this->processResults($response);
    }

    public function getCryptoPrices($total, $currencyCodes, $fiatCurrencyCode, $token)
    {
    
        $method = 'get-crypto-prices';
        $url = $this->getFullUrl($method);
        
        $data = [
            'currencies' => implode(',', $currencyCodes),
            'shop_currency' => $fiatCurrencyCode,
            'token' => $token,
            'amount' => $total
        ];

        $response = $this->http->post($url, ['body' => $data]);
        return $this->processResults($response);
    }

    private function getFullUrl($method){
       
        $baseUrl = $this->isSandbox ? $this->sandboxUrl : $this->baseUrl;
        return $baseUrl . $method;
    }

    private function processResults($response){

        if(!is_array($response)){
            return [
                'success' => 'false',
                'status' => 500
            ];
           
        }
        $data  = is_array($response) &&  isset($response['body']) ? json_decode($response['body'], true) : [];

        if(isset($data['data'] ) && !empty($data['data'])){
            $data = $data['data'];
        }
        return [
            'success' => $response['response']['code'] >= 200  && $response['response']['code'] < 300 ? true :false,
            'status' => $response['response']['code'] ,
            'data' => $data
        ];
    }
}
