<?php
/*
Plugin Name: 		MigPayments WooCommerce
Plugin URI: 		https://migpayments.tech
Description: 		A crypto payment gateway
Version: 			1.0.1
Author: 			Omnitask
Author URI: 		https://migpayments.tech
*/
require_once('src/WC_Migpayments_Service.php');
require_once __DIR__.'/vendor/autoload.php';
use chillerlan\QRCode\{QRCode, QROptions};
 
if (!defined( 'ABSPATH' )) exit; // Exit if accessed directly

if (!function_exists('migpayments_wc_gateway_load') && !function_exists('migpayments_wc_action_links')) // Exit if duplicate
{

	
	DEFINE('MIGPAYMENTSWC', 'migpayments-woocommerce');
	DEFINE('MIGPAYMENTSWC_VERSION', '1.3.8');
	DEFINE('MIGPAYMENTSWC_2WAY', json_encode(array("ETH", "BTC", "USDT")));


	if (!defined('MIGPAYMENTSWC_AFFILIATE_KEY'))
	{
		DEFINE('MIGPAYMENTSWC_AFFILIATE_KEY', 	'migpayments');
		add_action( 'plugins_loaded', 		'migpayments_wc_gateway_load', 20 );
		add_filter( 'plugin_action_links', 	'migpayments_wc_action_links', 10, 2 );
		add_action( 'wp_head', 'migpayments_wc_style' );

		
	}

	function migpayments_wc_style() {
		wp_enqueue_style('migpayments_wc_style', WP_PLUGIN_URL. '/migpayments/assets/css/migpayments_style.css');
	 }
	
 
	function migpayments_wc_action_links($links, $file)
	{
		static $this_plugin;

		if (!class_exists('WC_Payment_Gateway')) return $links;

		if (false === isset($this_plugin) || true === empty($this_plugin)) {
			$this_plugin = plugin_basename(__FILE__);
		}

		if ($file == $this_plugin) {
			$settings_link = '<a href="'.admin_url('admin.php?page=wc-settings&tab=checkout&section=wc_gateway_migpayments').'">'.__( 'Settings', MIGPAYMENTSWC ).'</a>';
			array_unshift($links, $settings_link);

			if (defined('MIGPAYMENTS'))
			{
				$unrecognised_link = '<a href="'.admin_url('admin.php?page='.MIGPAYMENTS.'payments&s=unrecognised').'">'.__( 'Unrecognised', MIGPAYMENTSWC ).'</a>';
				array_unshift($links, $unrecognised_link);
				$payments_link = '<a href="'.admin_url('admin.php?page='.MIGPAYMENTS.'payments&s=migpaymentswoocommerce').'">'.__( 'Payments', MIGPAYMENTSWC ).'</a>';
				array_unshift($links, $payments_link);
			}
		}

		return $links;
	}

	function migpayments_wc_gateway_add( $methods )
	{
		if (!in_array('WC_Gateway_Migpayments', $methods)) {
			$methods[] = 'WC_Gateway_MigPayments';
		}
		return $methods;
	}

	 
	function migpayments_wc_gateway_load()
	{

		// WooCommerce required
		if (!class_exists('WC_Payment_Gateway') || class_exists('WC_Gateway_MigPayments')) return;

		add_filter( 'woocommerce_payment_gateways', 		'migpayments_wc_gateway_add' );
		add_action('woocommerce_admin_order_data_after_billing_address', 	'migpayments_wc_admin_order_stats');
 		/*
		*	Payment Gateway WC Class
		*/
		class WC_Gateway_MigPayments extends WC_Payment_Gateway
		{
			private $isSandbox  = true;
			private $showCryptoPrices = true;
			private $fiatCurrencies         = ['EUR', 'USD'];
			private $cryptoCurrencies         = ['BTC' => 'BTC', 'ETH' => 'ETH', 'USDT' => 'USDT'];
			private $availableCurrencies = [];
			private $fiatCurrency = null;
			private $mainplugin_url     = '';
			private $url                = '';
			private $url3               = '';
			private $qrCodeWidthPx         = 200;
			private $cryptoPricesHtmlResponse = null;
			private $apiWhitelistedIpAddresses = ['165.22.81.95'];

			public function __construct()
			{
				global $migpayments;


				$this->id                 	= 'migpaymentspayments';
				$this->mainplugin_url 		= admin_url("plugin-install.php?tab=search&type=term&s=MigPayments");
				$this->method_title       	= __( 'Migpayments', MIGPAYMENTSWC );
				$this->method_description  	= __( "Supports BTC,ETH, USDT", MIGPAYMENTSWC ) . '</b><br>';
				$this->supports 			= ['products'];
				$this->has_fields = true;
											// Logo on Checkout Page
				$this->icon = apply_filters('woocommerce_migpaymentspayments_icon', plugins_url("/assets/img/logo.png", __FILE__));

			
				if (class_exists('migpaymentsclass') && defined('MIGPAYMENTS') && defined('MIGPAYMENTS_ADMIN') && is_object($migpayments))
				{
						$this->cryptoCurrencies			= $migpayments->cryptoCurrencies(); 	// All Coins
						$this->languages			= $migpayments->languages(); 		// All Languages

					$this->url		= MIGPAYMENTS_ADMIN.MIGPAYMENTS."settings";
					$this->url2		= MIGPAYMENTS_ADMIN.MIGPAYMENTS."payments&s=migpaymentswoocommerce";
					$this->url3		= MIGPAYMENTS_ADMIN.MIGPAYMENTS;
				}
				else
				{
				
					$this->url		= $this->mainplugin_url;
					$this->url2		= $this->url;
					$this->url3		= $this->url;
					$this->cointxt 	= '<b>'.__( 'Please install MigPayments Gateway WP Plugin', MIGPAYMENTSWC ).' &#187;</b>';

				}
				$this->fiatCurrency = get_woocommerce_currency();
	
				// Load the settings.
				$this->init_form_fields();
				$this->init_settings();
				$this->migpayments_settings();
				
				$apiWhitelistedIps = $this->get_option('api_whitelisted_ips');
				if($apiWhitelistedIps){
					$apiWhitelistedIps = explode(',', trim($apiWhitelistedIps));
					if(count($apiWhitelistedIps))
						$this->apiWhitelistedIpAddresses = array_merge($this->apiWhitelistedIpAddresses, $apiWhitelistedIps);
				}
		 
				$availableCurrenciesSetting = $this->get_option('available_currencies');
			
				if($availableCurrenciesSetting && is_array($availableCurrenciesSetting)){
					$availableCurrencies = []; 
				
					foreach($availableCurrenciesSetting as $key => $currencyCode){
						error_log(print_r($currencyCode, true));
						if(isset($this->cryptoCurrencies[$currencyCode]))
							$availableCurrencies[$currencyCode] = $this->cryptoCurrencies[$currencyCode];
					}
					
					if(count($availableCurrencies))
						$this->cryptoCurrencies = $availableCurrencies;
				}
 
				if(WC()->cart && $this->showCryptoPrices)
					$this->cryptoPricesHtmlResponse  = WC_Migpayments_Service::getCryptoPricesHtml(WC()->cart->get_total(false), $this->cryptoCurrencies, 'EUR', $this->get_option('api_token'), $this->isSandbox );
				
	
				//generate payment data
				add_action( 'woocommerce_thankyou_migpaymentspayments', array( $this, 'getPaymentData' ) );

				//update payment options(woocmmerce settings - payments)
				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

				//payment confirmed webhook
				add_action( 'woocommerce_api_crypto-payment-confirmed', array( $this, 'paymentConfirmedWebhook' ) );
			 
				return true;
			}
			 
			function migpayments_wc_validate($data, $errors = NULL   ){
				if ( !isset( $data['crypto_curency'] ) ) {
					wp_send_json_error( [ 'messages' => ['Crypto payment is not avaialble for '. $this->fiatCurrency .' shop currency.'], 'status' => 'notok' ] );
					
				 } 
				//  wp_send_json_success(['success']);
				 
			}
			public function paymentConfirmedWebhook(){
				error_log($_SERVER['REMOTE_ADDR']);
				if(!in_array($_SERVER['REMOTE_ADDR'], $this->apiWhitelistedIpAddresses)){
					wp_send_json_error( [ 'messages' => ['Forbidden' ]]);
				}
				$order = wc_get_order( $_GET['id'] );
				$order->update_status('completed', __('Order payment completed.', MIGPAYMENTSWC));
				$order->payment_complete();
			
				wp_send_json('success');
			}

			
			private function migpayments_settings()
			{

				// Define user set variables
				$this->isSandbox          = ((MIGPAYMENTSWC_AFFILIATE_KEY=='migpayments' && $this->get_option('is_sandbox')==='') || $this->get_option('is_sandbox') == 'yes' || $this->get_option('is_sandbox') == '1' || $this->get_option('is_sandbox') === true) ? true : false;
				$this->showCryptoPrices          = ((MIGPAYMENTSWC_AFFILIATE_KEY=='migpayments' && $this->get_option('show_crypto_prices')==='') || $this->get_option('show_crypto_prices') == 'yes' || $this->get_option('show_crypto_prices') == '1' || $this->get_option('show_crypto_prices') === true) ? true : false;
				
				$this->apiToken          = $this->get_option( 'api_token' );
				$this->title            = $this->get_option( 'title' );
				$this->description      = $this->get_option( 'description' );
				$this->qrCodeWidthPx       = trim(str_replace("px", "", $this->get_option( 'qrCodeWidthPx' )));
	
				if (!is_numeric($this->qrCodeWidthPx) || $this->qrCodeWidthPx < 0 || $this->qrCodeWidthPx > 500)     $this->qrCodeWidthPx 	= 200;

			
				return true;
			}

			 
			//default WC method
			public function init_form_fields()
			{
 
				$this->form_fields = array(
					'is_sandbox'		=> array(
						'title'   	  	=> __( 'Sandbox Mode', MIGPAYMENTSWC ),
						'type'    	  	=> 'checkbox',
						'default'	  	=> (MIGPAYMENTSWC_AFFILIATE_KEY=='migpayments'?'yes':'no'),
						'label'   	  	=> sprintf(__( "Choose to use sandbox or production enviroment", MIGPAYMENTSWC ), $this->url3)
					),
					'title'			=> array(
						'title'       	=> __( 'Title', MIGPAYMENTSWC ),
						'type'        	=> 'text',
						'default'     	=> __( 'Crypto Payment', MIGPAYMENTSWC ),
						'description' 	=> __( 'Payment method title that the customer will see on your checkout', MIGPAYMENTSWC )
					),
					'description' 	=> array(
						'title'       	=> __( 'Description', MIGPAYMENTSWC ),
						'type'        	=> 'textarea',
						'default'     	=> __( 'Secure, anonymous payment with virtual currency.', MIGPAYMENTSWC),
						'description' 	=> __( 'Payment method description that the customer will see on your checkout', MIGPAYMENTSWC )
					),
					'api_token' 	=> array(
						'title'       	=> __( 'Migpayments API Token', MIGPAYMENTSWC ),
						'type'        	=> 'text',
						'default'     	=> null,
						'description' 	=> __( '', MIGPAYMENTSWC )
					),
					'qrCodeWidthPx'	=> array(
						'title'       	=> __( 'QR Code Size(px)', MIGPAYMENTSWC ),
						'type'        	=> 'text',
						'label'        	=> 'px',
						'default'     	=> "200",
						'description' 	=> sprintf(__( "QRcode image size in payment box. Enter 0 to hide QR code. Default width: 200px. Maximum width: 500px.", MIGPAYMENTSWC ))
					),
					'show_crypto_prices'		=> array(
						'title'   	  	=> __( 'Crypto Totals Box', MIGPAYMENTSWC ),
						'type'    	  	=> 'checkbox',
						'default'	  	=> (MIGPAYMENTSWC_AFFILIATE_KEY=='migpayments'?'yes':'no'),
						'label'   	  	=> sprintf(__( "Show cart total converted to available crypto currencies on checkout page.", MIGPAYMENTSWC ), $this->url3)
					),
					'available_currencies' => array(
						'title'       	=> __( 'Available Crypto Currencies', MIGPAYMENTSWC ),
						'type' => 'multiselect',
						'label'      => __( 'Available Currencies', MIGPAYMENTSWC),
						'description'      => __( 'Check currency to make it visibile on checkout page. All currencies are visible by deafult.', MIGPAYMENTSWC ),
						'required'  => false,
						'default' => $this->cryptoCurrencies,
						'class'             => 'wc-enhanced-select',
						'css'               => 'width: 400px;',
						'options' => $this->cryptoCurrencies,
						'custom_attributes' => array(
							'data-placeholder' => __( 'Select crypto currencies', MIGPAYMENTSWC ),
						  ),
					),
					'api_whitelisted_ips' 	=> array(
						'title'       	=> __( 'Webhook Whitelisted IPs', MIGPAYMENTSWC ),
						'type'        	=> 'text',
						'default'     	=> null,
						'description' 	=> __( 'Enter comma separated IP addresses whilisted for payment webhook notifications.', MIGPAYMENTSWC )
					),
					
				);

				return true;
			}

			//default WC method
			public function payment_fields() {
	
			
				if ( $this->description ) {
					echo wpautop( wp_kses_post( $this->description ) );
				}
			
				// I will echo() the form, but you can close PHP tags and print it directly in HTML
				echo '<fieldset id="wc-' . esc_attr( $this->id ) . '-crypto-payment-form"  style="background:transparent;">';
			
				// Add this action hook if you want your custom payment gateway to support it
				do_action( 'woocommerce_crypto_payment_form_start', $this->id );
				
				
				

				if($this->cryptoPricesHtmlResponse && !$this->cryptoPricesHtmlResponse->error)
					echo $this->cryptoPricesHtmlResponse->data;
			 
			

				if(!in_array($this->fiatCurrency, $this->fiatCurrencies)){
					echo '<div class="wc-error"> Crypto payment method is not available for '. $this->fiatCurrency .' shop currency.</div>';
					return;
				}
				echo '<div class="form-row form-row-first">
						<label>Choose Crypto Currency<span class="required">*</span></label>
						<select id="crypto_currency" name="crypto_currency">';
				
				foreach($this->cryptoCurrencies as $k => $v)
				{
					echo '<option value="'. $k .'"> '. $v.' </option>';
				}

						 
						
				echo '	</select>
						</div>
					<div class="clear"></div>';
			
				do_action( 'woocommerce_crypto_payment_form_end', $this->id );
			
				echo '<div class="clear"></div></fieldset>';
			
			}

 

			//default WC method
			public function process_payment( $orderId )
			{
			
				// New Order
				$order = new WC_Order( $orderId );

				$orderId    = (true === version_compare(WOOCOMMERCE_VERSION, '3.0', '<')) ? $order->id          : $order->get_id();
				$userID      = (true === version_compare(WOOCOMMERCE_VERSION, '3.0', '<')) ? $order->user_id     : $order->get_user_id();
					
				$order->update_status('pending', __('Awaiting payment notification from MigPayments', MIGPAYMENTSWC));

			
				// Payment Page
				$payment_link = $this->get_return_url($order);
		
				$orderpage = $order->get_checkout_order_received_url()."&prvw=1";

				if (!get_post_meta( $orderId, '_migpayments_worder_orderid', true ))
				{
					update_post_meta( $orderId, '_migpayments_worder_crypto_currency_code', 	    $_POST['crypto_currency'] );
					update_post_meta( $orderId, '_migpayments_worder_orderid', 	    $orderId );
					update_post_meta( $orderId, '_migpayments_worder_userid', 	    $userID );
					update_post_meta( $orderId, '_migpayments_worder_createtime',   gmdate("c") );

					update_post_meta( $orderId, '_migpayments_worder_orderpage',     $orderpage );
					update_post_meta( $orderId, '_migpayments_worder_created',      gmdate("d M Y, H:i") );
		
				}
		
				// Empty cart
				WC()->cart->empty_cart();
				
				// Return redirect
				return array(
					'result' 	=> 'success',
					'redirect'	=> $payment_link
				);
			}
		
			public function getPaymentData( $orderId )
			{
				
				$order = new WC_Order( $orderId );

				$orderId       = (true === version_compare(WOOCOMMERCE_VERSION, '3.0', '<')) ? $order->id             : $order->get_id();
				$order_status   = (true === version_compare(WOOCOMMERCE_VERSION, '3.0', '<')) ? $order->status         : $order->get_status();
				$post_status    = (true === version_compare(WOOCOMMERCE_VERSION, '3.0', '<')) ? $order->post_status    : get_post_status( $orderId );
				$userID         = (true === version_compare(WOOCOMMERCE_VERSION, '3.0', '<')) ? $order->user_id        : $order->get_user_id();
				$fiatCurrencyCode = (true === version_compare(WOOCOMMERCE_VERSION, '3.0', '<')) ? $order->order_currency : $order->get_currency();
				$orderTotal    = (true === version_compare(WOOCOMMERCE_VERSION, '3.0', '<')) ? $order->orderTotal    : $order->get_total();
				$cryptoCurrencyCode = get_post_meta( $orderId, '_migpayments_worder_crypto_currency_code', true );
			
				if ($order === false)
				{
					echo '<br><h2>' . __( 'Information', MIGPAYMENTSWC ) . '</h2>' . PHP_EOL;
					echo "<div class='woocommerce-error'>". sprintf(__( 'The MigPayments payment plugin was called to process a payment but could not retrieve the order details for orderID %s. Cannot continue!', MIGPAYMENTSWC ), $orderId)."</div>";
				}
				elseif ($order_status == "cancelled" || $post_status == "wc-cancelled")
				{
					echo '<br><h2>' . __( 'Information', MIGPAYMENTSWC ) . '</h2>' . PHP_EOL;
					echo "<div class='woocommerce-error'>". __( "This order's status is 'Cancelled' - it cannot be paid for. Please contact us if you need assistance.", MIGPAYMENTSWC )."</div>";
				}
				else
				{
					
				
					$response  = WC_Migpayments_Service::getPaymentData($orderTotal, $cryptoCurrencyCode, $fiatCurrencyCode, $orderId, $this->apiToken, $this->isSandbox );
					$responseHtml = '<div class="woocommerce-error">Failed to get crypto payment data.</div>';
					
					// $response['success'] = true;
					// $response['data']['cryptoAddress'] = '0x1234';
					// $response['data']['calculatedAmount'] = 123;
					if(!$response->error && $response->data){
						$data = $response->data;

						update_post_meta( $orderId, '_migpayments_worder_fiat_amount', $orderTotal );
						update_post_meta( $orderId, '_migpayments_worder_crypto_amount',  $data['calculatedAmount']);
						update_post_meta( $orderId, '_migpayments_worder_crypto_address',  $data['cryptoAddress']);


						$responseHtml = 'Crypto address: '. $data['cryptoAddress'] .'<br>';
						$responseHtml .= '<img id="migpayments-address-qr-code" style="width:'. $this->qrCodeWidthPx . 'px;" src=" '.(new QRCode)->render($data['cryptoAddress']).'" alt="QR Code" />';
						$responseHtml .= 'Amount: '. $data['calculatedAmount'] .' ' .$data['currency'].'<br>';
					}

					echo $responseHtml;
				}
				
				return  true;
			}
	
		}
		// end class WC_Gateway_MigPayments

	}	

	// Admin Order details crypto payment info
  	function migpayments_wc_admin_order_stats( $order )
	{
 
	    $orderId     = (true === version_compare(WOOCOMMERCE_VERSION, '3.0', '<')) ? $order->id          : $order->get_id();
	    

		$cryptoCurrencyCode      = get_post_meta( $orderId, '_migpayments_worder_crypto_currency_code', true );
		$cryptoAmount      = get_post_meta( $orderId, '_migpayments_worder_crypto_amount', true );
		$cryptoAddress      = get_post_meta( $orderId, '_migpayments_worder_crypto_address', true );
	    
	  	 
		$htmlResponse = '<h3>Crypto Payment Info</h3>';
		$htmlResponse .= 'Crypto Currency: '. $cryptoCurrencyCode . '<br>';
		$htmlResponse .= 'Crypto Amount: '. $cryptoAmount. '<br>';
		$htmlResponse .= 'Crypto Address: '. $cryptoAddress. '<br>';
		
		echo $htmlResponse;
	   
		return;
	}
 
}