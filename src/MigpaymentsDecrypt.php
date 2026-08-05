<?php
require_once 'MigpaymentsLibrary.php';
require_once 'MigpaymentsResponse.php';

class MigpaymentsDecrypt
{
 
    public static function decryptData($encryptedData, $publicKey){
        $error = null;
        $data = null;
        $log = null;
        $defaultErroMsg = 'Failed to authorize';
        $context = ['source' => 'migpayments'];

        if(function_exists('wc_get_logger'))
        {
            $log = wc_get_logger();
        }
       
        try{
           
            $publicKey = openssl_pkey_get_public($publicKey);

            if($publicKey === false)
            {
                if($log)
                {
                    $log->error('Invalid public key.', $context );
                }
                return new MigpaymentsResponse($defaultErroMsg);
            }
            
            
            $encryptedData = base64_decode($encryptedData);
            openssl_public_decrypt($encryptedData, $decryptedData, $publicKey, OPENSSL_PKCS1_PADDING);

            if (is_null($decryptedData)) {
               
                return new MigpaymentsResponse($defaultErroMsg);
            }
            
            $data = json_decode($decryptedData);
             

        }catch(Exception $e){
           
            $error =  esc_html($e->getMessage());
            if($log)
            {
                $log->error('Failed to decrypt data: '. $error, $context);
            }
        }
         
        return new MigpaymentsResponse($error, $data);
    }
 
 
}
