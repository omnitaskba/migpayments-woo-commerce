<?php
/*
Plugin Name: 		PayByCrypto WooCommerce
Plugin URI: 		https://migpayments.tech
Description: 		A crypto payment gateway.
Version: 			1.7.3
Author: 			CryptoOrange
Author URI: 		https://cryptoorange.com
*/
 
require_once 'src/MigpaymentsService.php';
require_once 'src/MigpaymentsDecrypt.php';
require_once __DIR__.'/vendor/autoload.php';
require 'plugin-update-checker/plugin-update-checker.php';

use chillerlan\QRCode\{QRCode, QROptions};
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

if (!defined( 'ABSPATH' )) {exit;} // Exit if accessed directly

if (!function_exists('migpaymentsWcLoadGateway') && !function_exists('migpaymentsWcActionLinks'))
{
 
	global $migpayments;
	global $cryptoCurrencies;

	$updateChecker = PucFactory::buildUpdateChecker(
		'https://github.com/omnitaskba/migpayments-woo-commerce/',
		__FILE__,
		'migpayments-woo-commerce'
	);
	 
	$updateChecker->setAuthentication('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');

	DEFINE('MIGPAYMENTSWC', 'migpayments-woocommerce');
	DEFINE('MIGPAYMENTSWC_VERSION', '1.7.3');

	if (!defined('MIGPAYMENTSWC_AFFILIATE_KEY')){
		
		DEFINE('MIGPAYMENTSWC_AFFILIATE_KEY', 	'migpayments');
		add_action( 'plugins_loaded', 		'migpaymentsWcLoadGateway', 20 );
		add_filter( 'plugin_action_links', 	'migpaymentsWcActionLinks', 10, 2 );
		add_action( 'wp_head', 'migpaymentsWcStyle' );
		add_action('wp_enqueue_scripts','migpaymentsWcScripts');
		add_filter( 'page_template', 'migpaymentsWcPageTemplate');
		add_action( 'wp_ajax_migpayments_wc_get_crypto_estimate', 'migpaymentsWcAsyncGetEstimate' );
		add_action( 'wp_ajax_nopriv_migpayments_wc_get_crypto_estimate', 'migpaymentsWcAsyncGetEstimate' );

		add_action( 'wp_ajax_migpayments_wc_get_payment_data', 'migpaymentsWcAsyncGetPaymentData' );
		add_action( 'wp_ajax_nopriv_migpayments_wc_get_payment_data', 'migpaymentsWcAsyncGetPaymentData' );
		
		add_action( 'wp_ajax_migpayments_wc_check_payment_status', 'migpaymentsWcCheckAsyncStatus' );
		add_action( 'wp_ajax_nopriv_migpayments_wc_check_payment_status', 'migpaymentsWcCheckAsyncStatus' );
		
		add_action( 'before_woocommerce_init', function() {
			if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		 
			}
		} );
		register_activation_hook(__FILE__, 'activatePlugin');
	}
	
	function activatePlugin () {
		createRedirectPage('migpayments-payment-instructions');
	}
	
	function createRedirectPage($pageName) {
		$pageExists = false;
		$pages = get_pages();
		foreach ($pages as $page) {
			if ($page->post_name == $pageName) {
				$pageExists = true;
				break;
			}
		}
		if (!$pageExists) {
			wp_insert_post ([
				'post_type' =>	'page',
				'post_title' => 'Migpayments Payment Instructions',
				'post_content' => '<strong>Please send whole amount in ONE transaction.</strong></br><strong>
									Please add the mining fee on top of the displayed amount.</strong></br><hr>',
				'post_name' => $pageName,
				'post_status' => 'publish',
				'post_type' => 'page',
				'meta_input' => ['visibility' => 'private'],
				
			]);
		}
	}

	function migpaymentsWcStyle() {
		wp_enqueue_style('migpaymentsWcStyle',
						plugin_dir_url(__FILE__) . '/assets/css/migpayments_style.css?v='. MIGPAYMENTSWC_VERSION);
	}
	
 
	function migpaymentsWcScripts(){
		if ( is_page( 'migpayments-payment-instructions' ) ) {

			wp_register_script( 'redirect-js',   plugin_dir_url(__FILE__) . '/assets/js/migpayments_scripts.js' );

			wp_localize_script(
				'redirect-js',
				'ajaxObj',
				array(
					'ajaxurl' => admin_url('admin-ajax.php'),
					'orderId' => $_GET['orderId'],
					'redirectUrl' => $_GET['success_url']
				)
			);
			
		 
			wp_enqueue_script( 'redirect-js' );
  
		}
 
	}

	function migpaymentsWcAsyncGetPaymentData()
	{
		$migpayments = new WcMigpaymentsGateway();
		
		$order = wc_get_order( $_POST['order_id'] );

		$orderId       = (true === version_compare(WOOCOMMERCE_VERSION, '3.0', '<')) ? $order->id             : $order->get_id();
		$fiatCurrencyCode = (true === version_compare(WOOCOMMERCE_VERSION, '3.0', '<')) ? $order->order_currency : $order->get_currency();
		$orderTotal    = (true === version_compare(WOOCOMMERCE_VERSION, '3.0', '<')) ? $order->orderTotal    : $order->get_total();
		$cryptoCurrencyCode =  $_POST['currency_code'];
			 
		$user = $order->get_user();

		$orderData = [
			'customer_email_address' =>  $user ? $user->user_email : null,
			'customer_username' =>  $user ? $user->user_login . '('. $user->display_name . ')' : null,
		 
			
		];

		$response  = MigpaymentsService::getPaymentDataHtml($orderTotal, $cryptoCurrencyCode, $fiatCurrencyCode,
															 $orderId, $migpayments->apiToken, $migpayments->isSandbox , $orderData );
		if(!$response->error)
			echo $response->data;
		wp_die();
	}


	function migpaymentsWcAsyncGetEstimate()
	{
		$migpayments = new WcMigpaymentsGateway();
		$order = wc_get_order( $_POST['order_id'] );
 
		$fiatCurrencyCode = (true === version_compare(WOOCOMMERCE_VERSION, '3.0', '<'))
								? $order->order_currency : $order->get_currency();
		$orderTotal    = (true === version_compare(WOOCOMMERCE_VERSION, '3.0', '<'))
								? $order->orderTotal    : $order->get_total();

		 
		$response  = MigpaymentsService::getCryptoPricesHtml($orderTotal, $migpayments->cryptoCurrencies,
																$fiatCurrencyCode, $migpayments->apiToken, $migpayments->isSandbox);
		
		if(!$response->error)
		{
			echo $response->data;
		} else {
			echo 'Failed to get crypto estimate';
		}
		
		wp_die();
	}


	function migpaymentsWcCheckAsyncStatus() {
		 
		$order = wc_get_order( $_POST['order_id'] );

		$status      = $order->get_meta('_migpayments_worder_crypto_payment_status', true );
		$currencyCode = $order->get_meta('_migpayments_worder_crypto_currency_code', true );
	 
		$data  = [
			'status' => $status,
			'currency_code' => $currencyCode,
			'order_number' => $_POST['order_id'],
		];

		switch($status){
			case 'Completed':
				$data['redirect'] = true;
				$data['redirectUrl'] = $order->get_meta('_return_url', true );
			break;
			case 'Overpaid':
				$data['overpaid_amount'] =  $order->get_meta('_migpayments_worder_overpaid_payment_amount', true );
 			break;
			case 'Partially paid':
				$data['partial_payments'] =  $order->get_meta('_migpayments_worder_partial_payments', true);

			break;
			default:
			break;
		}
		 
		wp_send_json($data);
	}
	
	function migpaymentsWcPageTemplate( $pageTemplate )
	{
		global $migpayments;
		$migpayments = new WcMigpaymentsGateway();
		
		if ( is_page( 'migpayments-payment-instructions' ) ) {
			$pageTemplate = dirname( __FILE__ ) . '/redirect.php';
			global $cryptoCurrencies;
			
		 	$cryptoCurrencies = $migpayments->cryptoCurrencies;
			
		}
		
		return $pageTemplate;
	 
		
	}

	function migpaymentsWcActionLinks($links, $file)
	{
		static $migpaymentsPlugin;

		if (!class_exists('WC_Payment_Gateway')) return $links;

		if (false === isset($migpaymentsPlugin) || true === empty($migpaymentsPlugin)) {
			$migpaymentsPlugin = plugin_basename(__FILE__);
		}

		if ($file == $migpaymentsPlugin) {
			$settingsLink = '<a href="'.admin_url('admin.php?page=wc-settings&tab=checkout&section=wc_gateway_migpayments').'">'
								.__( 'Settings', MIGPAYMENTSWC ).'</a>';
			array_unshift($links, $settingsLink);

			if (defined('MIGPAYMENTS'))
			{
				$unrecognizedLink = '<a href="'.admin_url('admin.php?page='.MIGPAYMENTS.'payments&s=unrecognised').'">'
									.__( 'Unrecognised', MIGPAYMENTSWC ).'</a>';
				array_unshift($links, $unrecognizedLink);
				$paymentsLink = '<a href="'.admin_url('admin.php?page='.MIGPAYMENTS.'payments&s=migpaymentswoocommerce').'">
									'.__( 'Payments', MIGPAYMENTSWC ).'</a>';
				array_unshift($links, $paymentsLink);
			}
		}

		return $links;
	}
	  
	function migpaymentsWcAddGateway( $methods )
	{
		if (!in_array('WC_Gateway_Migpayments', $methods)) {
			$methods[] = 'WcMigpaymentsGateway';
		}
		return $methods;
	}

	// Admin Order details crypto payment info
	function migpayments_wc_admin_order_stats( $order )
	{
 
		$status      = $order->get_meta('_migpayments_worder_crypto_payment_status', true );
		$cryptoCurrencyCode      = $order->get_meta('_migpayments_worder_crypto_currency_code', true );
		$cryptoAmount      = $order->get_meta('_migpayments_worder_crypto_amount', true );
		$cryptoAddress      = $order->get_meta('_migpayments_worder_crypto_address', true );
		
		   
		$htmlResponse = '<h3>Crypto Payment Info</h3>';
		$htmlResponse .= 'Payment Status: '. $status . '<br>';
		$htmlResponse .= 'Crypto Currency: '. $cryptoCurrencyCode . '<br>';
		$htmlResponse .= 'Crypto Amount: '. $cryptoAmount. '<br>';
		$htmlResponse .= 'Crypto Address: '. $cryptoAddress. '<br>';
		
		echo $htmlResponse;
	   
		return;
	}


	function wooCommerceAvailableGateways( $availableGateways ) {
    	if (! is_checkout() ) {return $availableGateways;}
		
		if (array_key_exists('migpaymentspayments',$availableGateways)) {
			
			$availableGateways['migpaymentspayments']->order_button_text = __( 'Proceed with PayByCrypto', 'woocommerce' );
		}
		return $availableGateways;
	}

	function migpaymentsWcLoadGateway()
	{
		// WooCommerce required
		if (!class_exists('WC_Payment_Gateway') || class_exists('WcMigpaymentsGateway')) return;

		add_filter( 'woocommerce_payment_gateways', 		'migpaymentsWcAddGateway' );
		add_action('woocommerce_admin_order_data_after_billing_address', 	'migpayments_wc_admin_order_stats');
 		add_filter( 'woocommerce_available_payment_gateways', 'wooCommerceAvailableGateways' );
 
		
 		/*
		*	Payment Gateway WC Class
		*/
		class WcMigpaymentsGateway extends WC_Payment_Gateway
		{
			public $isSandbox  = true;
			public $apiToken = null;
			private $publicKey = null;
			public $fiatCurrencies         = ['EUR', 'USD'];
			public $cryptoCurrencies         = ['BTC' => 'BTC', 'ETH' => 'ETH', 'USDT' => 'USDT'];
			public $fiatCurrency = null;
			public $url3  = '';
			public $mainPluginUrl;
 			public $cryptoAddressLabelTxt = 'address';
			public $cryptoAmountLabelTxt = 'Send this exact amount:';
			public $paymentDataErrorTxt = 'Failed to get crypto payment data.';
			public $log;
			public $icon;
			public $description;
			public $context = ['source' => 'migpayments'];
			public $redirectLogoUrl;
			public $redirectBackgroundUrl;
			public $redirectBackgroundColor;

			public function __construct()
			{
				global $migpayments;
				if(function_exists('wc_get_logger')){
					$this->log = wc_get_logger();
				}
 
				$this->id                 	= 'migpaymentspayments';
				$this->mainPluginUrl 		= admin_url("plugin-install.php?tab=search&type=term&s=MigPayments");
				$this->method_title       	= __( 'PayByCrypto', MIGPAYMENTSWC );
				$this->method_description  	= __( "Cryptocurrency Payment Gateway: Accept BTC, ETH, and USDT with ease.", MIGPAYMENTSWC ) . '</b><br>';
				$this->supports 			= ['products'];
				$this->has_fields = true;
				$this->icon = apply_filters('woocommerce'. $this->id.'icon', plugins_url("/assets/img/currencies.png", __FILE__));
				$this->fiatCurrency = get_woocommerce_currency();
		 
				if (class_exists('migpaymentsclass') && defined('MIGPAYMENTS')  && is_object($migpayments))
				{
					$this->cryptoCurrencies			= $migpayments->cryptoCurrencies();
					$this->url		= MIGPAYMENTS_ADMIN.MIGPAYMENTS."settings";
					$this->url2		= MIGPAYMENTS_ADMIN.MIGPAYMENTS."payments&s=migpaymentswoocommerce";
					$this->url3		= MIGPAYMENTS_ADMIN.MIGPAYMENTS;
				}
				 
	
				// Load the settings.
				$this->init_form_fields();
				$this->init_settings();
				$this->migpaymentsSettings();
			 
				$availableCurrenciesSetting = $this->get_option('available_currencies');
			
				if ($availableCurrenciesSetting && is_array($availableCurrenciesSetting)) {
					$availableCurrencies = array_intersect_key($this->cryptoCurrencies, array_flip($availableCurrenciesSetting));
					$this->cryptoCurrencies = $availableCurrencies;
				}
 
		
				//update payment options(woocmmerce settings - payments)
				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
				//Webhooks
				add_action( 'woocommerce_api_crypto-payment-confirmed', array( $this, 'paymentConfirmedWebhook' ) );
				add_action( 'woocommerce_api_crypto-partial-payment', array( $this, 'partialPaymentWebhook' ) );
				add_action( 'woocommerce_api_crypto-overpaid-payment', array( $this, 'overpaidPaymentWebhook' ) );
				
				return true;
			}
			 
			private function decryptData($methodName = ''){
				$body = file_get_contents('php://input');
				$headers = getallheaders();

				parse_str($body, $parsedData);
				
				$encryptedData = isset($parsedData['encryptedData']) ? $parsedData['encryptedData'] : '';
					
				if($this->log){
					$this->log->info($methodName .' - Trying to decrypt data. Headers:' . json_encode($headers), $this->context);
				}

				$decryptResponse = MigpaymentsDecrypt::decryptData($encryptedData, $this->publicKey);
				if($decryptResponse->error)
				{
					if($this->log)
						$this->log->error($methodName .' error. Failed to decrypt data: ' .$decryptResponse->error, $this->context);
					return wp_send_json_error($decryptResponse->error, 403);
				}
				

				return $decryptResponse->data;
				 
			}

		
			public function paymentConfirmedWebhook(){
			 
				$data = $this->decryptData('Payment Confirmed Webhook');
				$this->log->info( 'Confirmed Payment Data Received:'. json_encode($data) ,  $this->context);

				try{
					$order = wc_get_order( $_GET['id'] );
					if(!$order){
						if($this->log)
							$this->log->error('Confirmed Payment Webhook: Failed to get order #'. ( isset($_GET['id']) ? $_GET['id'] : ''), $this->context);
						throw new \Exception("Failed to get order.");
					}

					$order->update_status('completed', __('Order payment completed.', MIGPAYMENTSWC));
					$order->payment_complete();

					$order->update_meta_data('_migpayments_worder_crypto_payment_status', 'Completed');
					$order->save();
				} catch(\Exception $e){
					if($this->log)
					{
						$this->log->error('Failed to confirm payment: ' .  esc_html($e->getMessage()), $this->context);
					}
					wp_send_json('Failed to confirm payment.', 406);
				}
				wp_send_json('success');
			}

			
			public function partialPaymentWebhook(){
			  	
				$data = $this->decryptData('Partial Payment Webhook');
				$this->log->info( 'Partial Payment Data Received:'. json_encode($data) ,  $this->context);
				 

				try{
					$order = wc_get_order( $_GET['id'] );
					if(!$order){
						if($this->log)
						{
							$this->log->error('Partial Payment Webhook: Failed to get order #'.  ( isset($_GET['id']) ? $_GET['id'] : ''), $this->context);
						}
						throw new \Exception("Failed to get order.");
					}
							
					$order->add_order_note('Partial crypto payment received with amount of '.$data->amount . ' ' . $data->crypto_currency. '.', true);
					$order->update_meta_data( '_migpayments_worder_crypto_payment_status', 'Partially paid');
					
					$partialPayments = 	$order->get_meta('_migpayments_worder_partial_payments', true);
					if(!$partialPayments){
						$partialPayments = [];
					}

					$payment =  [
						'amount' => $data->amount,
						'received_at' => current_time('d.m.Y H:i'),
						'currency_code' => $data->crypto_currency
					];

					$partialPayments[] = $payment;
					
					$order->update_meta_data('_migpayments_worder_partial_payments', $partialPayments);
					$order->save();

				} catch(\Exception $e){
					if($this->log)
						$this->log->error('Failed to store partial payment: ' .  esc_html($e->getMessage()), $this->context);
					wp_send_json('Failed to store partial payment notification.', 406);
				}
 				wp_send_json('success');
			}

			public function overpaidPaymentWebhook(){
			  	
				$data = $this->decryptData('Overpaid Payment Webhook');
				$this->log->info( 'Overpaid Payment Data Received:'. json_encode($data) ,  $this->context);

				try{

					$order = wc_get_order( $_GET['id'] );
					if(!$order){
						if($this->log)
						{
							$this->log->error('Overpaid Payment Webhook: Failed to get order #'.  ( isset($_GET['id']) ? $_GET['id'] : ''), $this->context);
						}
						throw new \Exception("Failed to get order.");
					}

					$order->add_order_note('Overpaid crypto payment received with amount of '.$data->amount . ' ' . $data->crypto_currency. '.', true);
					$order->update_meta_data('_migpayments_worder_crypto_payment_status', 'Overpaid');
					$order->update_meta_data('_migpayments_worder_overpaid_payment_amount', $data->amount);
					$order->save();

				} catch(\Exception $e){
					if($this->log)
					{
						$this->log->error('Failed to receive overpaid notification: '.  esc_html($e->getMessage()), $this->context);
					}
					wp_send_json('Failed to store overpaid payment notification.', 406);
				}
				 
 				wp_send_json('success');
			}

			
			private function migpaymentsSettings()
			{

				// Define user set variables
				$this->isSandbox = ((MIGPAYMENTSWC_AFFILIATE_KEY=='migpayments' && $this->get_option('is_sandbox')==='') || $this->get_option('is_sandbox') == 'yes' || $this->get_option('is_sandbox') == '1' || $this->get_option('is_sandbox') === true) ? true : false;
				$this->apiToken = ltrim(rtrim($this->get_option( 'api_token' )));
				$this->publicKey = ltrim(rtrim($this->get_option( 'public_key' )));
				$this->redirectLogoUrl =  $this->get_option( 'redirect_page_logo' ) && $this->get_option( 'redirect_page_logo' ) != '' ? $this->get_option( 'redirect_page_logo' ) : $this->redirectLogoUrl; 
				$this->redirectBackgroundUrl =  $this->get_option( 'redirect_page_background' ) && $this->get_option( 'redirect_page_background' ) != '' ? $this->get_option( 'redirect_page_background' ) : $this->redirectBackgroundUrl;
				$this->title = $this->get_option( 'title' );
				$this->description = $this->get_option( 'description' );
				$this->cryptoAddressLabelTxt = $this->get_option( 'crypto_address_label_txt' ) && $this->get_option( 'crypto_address_label_txt' ) != '' ? $this->get_option( 'crypto_address_label_txt' ) : $this->cryptoAddressLabelTxt;
				$this->cryptoAmountLabelTxt = $this->get_option( 'crypto_amount_label_txt' ) && $this->get_option( 'crypto_amount_label_txt' ) != '' ? $this->get_option( 'crypto_amount_label_txt' ) : $this->cryptoAmountLabelTxt;
				$this->paymentDataErrorTxt = $this->get_option( 'payment_data_error_txt' ) && $this->get_option( 'payment_data_error_txt' ) != '' ? $this->get_option( 'payment_data_error_txt' ) : $this->paymentDataErrorTxt;
				$this->redirectBackgroundColor =  $this->get_option( 'redirect_page_background_color' ) && $this->get_option( 'redirect_page_background_color' ) != '' ? $this->get_option( 'redirect_page_background_color' ) : $this->redirectBackgroundUrl;
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
						'label'   	  	=> sprintf(__( "Choose to use sandbox or production enviroment", MIGPAYMENTSWC ), $this->url3),
					),

					'api_token' 	=> array(
						'title'       	=> __( 'PayByCrypto API Key', MIGPAYMENTSWC ),
						'type'        	=> 'text',
						'default'     	=> null,
						'description' 	=> __( '', MIGPAYMENTSWC )
					),
					'public_key' 	=> array(
						'title'       	=> __( 'PayByCrypto Public Key', MIGPAYMENTSWC ),
						'type'        	=> 'textarea',
						'default'     	=> null,
						'description' 	=> __( '', MIGPAYMENTSWC )
					),
			  
					'available_currencies' => array(
						'title'       	=> __( 'Available Crypto Currencies', MIGPAYMENTSWC ),
						'type' => 'multiselect',
						'label'      => __( 'Available Currencies', MIGPAYMENTSWC),
						'desc_tip'      => __( 'Check currency to make it visibile on checkout page. All currencies are visible by deafult.', MIGPAYMENTSWC ),
						'required'  => false,
						'default' => $this->cryptoCurrencies,
						'class'             => 'wc-enhanced-select',
						'css'               => 'width: 400px;',
						'options' => $this->cryptoCurrencies,
						'custom_attributes' => array(
							'data-placeholder' => __( 'Select crypto currencies', MIGPAYMENTSWC ),
						  ),
					),
					'title'			=> array(
						'title'       	=> __( 'Title', MIGPAYMENTSWC ),
						'type'        	=> 'text',
						'default'     	=> __( 'PayByCrypto', MIGPAYMENTSWC ),
						'desc_tip' 	=> __( 'Payment method title that the customer will see on your checkout', MIGPAYMENTSWC )
					),
					'description' 	=> array(
						'title'       	=> __( 'Description', MIGPAYMENTSWC ),
						'type'        	=> 'textarea',
						'default'     	=> __( 'Secure, anonymous payment with virtual currency.', MIGPAYMENTSWC),
						'desc_tip' 	=> __( 'Payment method description that the customer will see on your checkout', MIGPAYMENTSWC )
					),
					'crypto_address_label_txt'			=> array(
						'title'       	=> __( 'Crypto Address Label', MIGPAYMENTSWC ),
						'type'        	=> 'text',
						'default'     	=> __( 'Send transaction to this address:', MIGPAYMENTSWC ),
						'desc_tip' 	=> __( 'Crypto address label text placed on payment instructions page.', MIGPAYMENTSWC )
					),
					'crypto_amount_label_txt'			=> array(
						'title'       	=> __( 'Crypto Amount Label', MIGPAYMENTSWC ),
						'type'        	=> 'text',
						'default'     	=> __( 'Send this exact amount: ', MIGPAYMENTSWC ),
						'desc_tip' 	=> __( 'Crypto amount label text placed on payment instructions page.', MIGPAYMENTSWC )
					),
					'payment_data_error_txt' 	=> array(
						'title'       	=> __( 'Payment Error Message', MIGPAYMENTSWC ),
						'type'        	=> 'textarea',
						'default'     	=> __( 'Failed to get crypto payment data.', MIGPAYMENTSWC),
						'desc_tip' 	=> __( 'Payment instructions error text.', MIGPAYMENTSWC )
					),
					
					'redirect_page_logo' 	=> array(
						'title'       	=> __( 'Logo URL', MIGPAYMENTSWC ),
						'type'        	=> 'text',
						'default'     	=> null,
						'desc_tip' 	=> __( 'Minimum Image Width: 120px', MIGPAYMENTSWC )
					),
					'redirect_page_background' 	=> array(
						'title'       	=> __( 'Background URL', MIGPAYMENTSWC ),
						'type'        	=> 'text',
						'default'     	=> null,
						'desc_tip' 	=> __( 'Minimum Image Width: 1400px', MIGPAYMENTSWC )
						
					),
					'redirect_page_background_color' 	=> array(
						'title'       	=> __( 'Background Color', MIGPAYMENTSWC ),
						'type'        	=> 'text',
						'default'     	=> null,
						'desc_tip' 	=> __( 'If Background URL is set it will override this setting.', MIGPAYMENTSWC ),
						'description' 	=> __( 'Color in hex/rgb/rgba format.', MIGPAYMENTSWC )
						
					),
				 
				);

				return true;
			}

			//default WC method
			public function payment_fields() {
			 
				if($this->description)
				{
					echo '<div id="wc-migpayments-payment-method-description">'. $this->description .'</div>';
				}

			}

			//default WC method
			public function process_payment( $orderId )
			{
   
				$order = wc_get_order($orderId);

				$orderId    = (true === version_compare(WOOCOMMERCE_VERSION, '3.0', '<')) ? $order->id          : $order->get_id();
				$userID      = (true === version_compare(WOOCOMMERCE_VERSION, '3.0', '<')) ? $order->user_id     : $order->get_user_id();
					
			 
				$paymentLink = $this->get_return_url($order);
		
				$orderpage = $order->get_checkout_order_received_url()."&prvw=1";

				if (!$order->get_meta('_migpayments_worder_orderid', true ))
				{
 	
					$order->update_meta_data('_migpayments_worder_crypto_payment_status', 'Pending');
					$order->update_meta_data('_migpayments_worder_crypto_currency_code', 	    $_POST['crypto_currency'] );
					$order->update_meta_data('_migpayments_worder_orderid', 	    $orderId );
					$order->update_meta_data('_migpayments_worder_userid', 	    $userID );
					$order->update_meta_data('_migpayments_worder_createtime',   gmdate("c") );
					$order->update_meta_data('_migpayments_worder_orderpage',     $orderpage );
					$order->update_meta_data('_migpayments_worder_created',      gmdate("d M Y, H:i") );
					 
				}
			 
				// Empty cart
				WC()->cart->empty_cart();
				
				$order->update_meta_data('_return_url', $paymentLink );
				$order->save();
			
				return array(
					'result' => 'success',
					'redirect' => site_url('migpayments-payment-instructions?orderId='.$orderId.'&success_url='. $this->get_return_url($order))
				);
			 
			}
		
			
			
		}
		// end class WcMigpaymentsGateway
 	}
}