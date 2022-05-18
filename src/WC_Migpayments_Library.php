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
 

    public function getPaymentData($total, $currencyCode, $orderNumber, $token)
    {
        $url = $this->baseUrl . 'rest/get-payment-data';
        error_log($url);
        $data = [
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

    private function processResults($response){
        return [
            'success' => $response['response']['code'] == 200 ? true :false,
            'status' => $response['response']['code'] ,
            'body' => json_decode($response['body'], true)
        ];
    }
}