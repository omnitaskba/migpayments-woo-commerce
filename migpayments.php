<?php
/*
Plugin Name: 		MigPayments WooCommerce
Plugin URI: 		https://migpayments.tech
Description: 		A crypto payment gateway
Version: 			1.0.2
Author: 			Omnitask
Author URI: 		https://migpayments.tech
*/
require_once('src/WC_Migpayments_Service.php');
require_once __DIR__.'/vendor/autoload.php';
use chillerlan\QRCode\{QRCode, QROptions};

if (!defined( 'ABSPATH' )) exit; // Exit if accessed directly

if (!function_exists('migpayments_wc_gateway_load') && !function_exists('migpayments_wc_action_links')) // Exit if duplicate
{
	global $paymentDataPageTitle;
	global $redirectBtnText;
	global $migpayments;
	
	DEFINE('MIGPAYMENTSWC', 'migpayments-woocommerce');
	DEFINE('MIGPAYMENTSWC_VERSION', '1.3.8');
	DEFINE('MIGPAYMENTSWC_2WAY', json_encode(array("ETH", "BTC", "USDT")));


	if (!defined('MIGPAYMENTSWC_AFFILIATE_KEY'))
	{
		DEFINE('MIGPAYMENTSWC_AFFILIATE_KEY', 	'migpayments');
		add_action( 'plugins_loaded', 		'migpayments_wc_gateway_load', 20 );
		add_filter( 'plugin_action_links', 	'migpayments_wc_action_links', 10, 2 );
		add_action( 'wp_head', 'migpayments_wc_style' );
		add_action('wp_enqueue_scripts','migpayments_wc_scripts');
		add_filter( 'page_template', 'migpayments_wc_page_template');
		add_action( 'wp_ajax_migpayments_wc_check_payment_status', 'migpayments_wc_asyncCheckPaymentStatus' );
		add_action( 'wp_ajax_nopriv_migpayments_wc_check_payment_status', 'migpayments_wc_asyncCheckPaymentStatus' );

		register_activation_hook(__FILE__, 'myplugin_activate'); 
	}
	
	function myplugin_activate () {
	  create_custom_page('migpayments-payment-instructions');
	}
	
	function create_custom_page($page_name) {
	  $pageExists = false;
	  $pages = get_pages();     
	  foreach ($pages as $page) { 
		if ($page->post_name == $page_name) {
		  $pageExists = true;
		  break;
		}
	  }
	  if (!$pageExists) {
		wp_insert_post ([
			'post_type' =>'page',
			'post_status' => 'private',
			'post_title' => 'Migpayments Payment Instructions' ,  
			'post_content' => '<strong>Please send whole amount in ONE transaction.</strong></br> <strong>Please add the mining fee on top of the displayed amount.</strong></br><hr>',     
			'post_name' => $page_name,
			'post_status' => 'publish',
			'post_type' => 'page',
			'meta_input' => ['visibility' => 'private'],
			
		]);
	  }
	}
	function migpayments_wc_style() {
		wp_enqueue_style('migpayments_wc_style', WP_PLUGIN_URL. '/migpayments/assets/css/migpayments_style.css?v=1.1');

	}
	
	function migpayments_wc_scripts(){
		if ( is_page( 'migpayments-payment-instructions' ) ) {

			wp_register_script( 'redirect-js',  WP_PLUGIN_URL. '/migpayments/assets/js/migpayments_scripts.js' );

			wp_localize_script( 
				'redirect-js', 
				'ajaxObj', 
				array( 
					'ajaxurl' => admin_url( 'admin-ajax.php' ) ,
					'orderId' => $_GET['orderId'],
					'redirectUrl' => $_GET['success_url']
				)
			);
		
			wp_enqueue_script( 'redirect-js' );
		 
		} 
 
	}
	

	function migpayments_wc_asyncCheckPaymentStatus() {
		global $migpayments;  
	 
		$status      = get_post_meta( $_POST['order_id'], '_migpayments_worder_crypto_payment_status', true );
		if($status === 'Completed')
			wp_send_json(['redirect' => true]);
		wp_send_json($status, 406);
	}
	
	 function migpayments_wc_page_template( $page_template )
	 {
		 if ( is_page( 'migpayments-payment-instructions' ) ) {
		 
			$page_template = dirname( __FILE__ ) . '/redirect.php';
		 }
	 
		 return $page_template;
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
	
	function migpayments_wc_get_redirectBtnText() {
		global $migpayments;
		$migpayments = new WC_Gateway_MigPayments();
		echo $migpayments->redirectBtnText;

	}

	function migpayments_wc_get_cryptoAmountLabelTxt() {
		global $migpayments;
		$migpayments = new WC_Gateway_MigPayments();
		echo $migpayments->cryptoAmountLabelTxt;

	}

	function migpayments_wc_get_cryptoAddressLabelTxt() {
		global $migpayments;
		$migpayments = new WC_Gateway_MigPayments();
		echo $migpayments->cryptoAddressLabelTxt;

	}

	function migpayments_wc_get_paymentDataErrorTxt() {
		global $migpayments;
		$migpayments = new WC_Gateway_MigPayments();
		echo $migpayments->paymentDataErrorTxt;

	}


	
	

	function migpayments_wc_gateway_add( $methods )
	{
		if (!in_array('WC_Gateway_Migpayments', $methods)) {
			$methods[] = 'WC_Gateway_MigPayments';
		}
		return $methods;
	}

	// Admin Order details crypto payment info
	function migpayments_wc_admin_order_stats( $order )
	{
 
		$orderId     = (true === version_compare(WOOCOMMERCE_VERSION, '3.0', '<')) ? $order->id          : $order->get_id();
		

		$status      = get_post_meta( $orderId, '_migpayments_worder_crypto_payment_status', true );
		$cryptoCurrencyCode      = get_post_meta( $orderId, '_migpayments_worder_crypto_currency_code', true );
		$cryptoAmount      = get_post_meta( $orderId, '_migpayments_worder_crypto_amount', true );
		$cryptoAddress      = get_post_meta( $orderId, '_migpayments_worder_crypto_address', true );
		
		   
		$htmlResponse = '<h3>Crypto Payment Info</h3>';
		$htmlResponse .= 'Payment Status: '. $status . '<br>';
		$htmlResponse .= 'Crypto Currency: '. $cryptoCurrencyCode . '<br>';
		$htmlResponse .= 'Crypto Amount: '. $cryptoAmount. '<br>';
		$htmlResponse .= 'Crypto Address: '. $cryptoAddress. '<br>';
		
		echo $htmlResponse;
	   
		return;
	}


function woocommerce_available_payment_gateways( $available_gateways ) {
    if (! is_checkout() ) return $available_gateways;  // stop doing anything if we're not on checkout page.
     if (array_key_exists('migpaymentspayments',$available_gateways)) {
        // Gateway ID for Paypal is 'paypal'. 
		
         $available_gateways['migpaymentspayments']->order_button_text = __( 'Proceed to Migpayments', 'woocommerce' );
    }
    return $available_gateways;
}

 
	function migpayments_wc_gateway_load()
	{
		
	
		// WooCommerce required
		if (!class_exists('WC_Payment_Gateway') || class_exists('WC_Gateway_MigPayments')) return;

		add_filter( 'woocommerce_payment_gateways', 		'migpayments_wc_gateway_add' );
		add_action('woocommerce_admin_order_data_after_billing_address', 	'migpayments_wc_admin_order_stats');
 		add_filter( 'woocommerce_available_payment_gateways', 'woocommerce_available_payment_gateways' );
		
 		/*
		*	Payment Gateway WC Class
		*/
		class WC_Gateway_MigPayments extends WC_Payment_Gateway
		{
			private $isSandbox  = true;
			private $showCryptoPrices = true;
			private $fiatCurrencies         = ['EUR', 'USD'];
			private $cryptoCurrencies         = ['BTC' => 'BTC', 'ETH' => 'ETH', 'USDT' => 'USDT'];
			private $fiatCurrency = null;
			private $url3               = '';
			private $cryptoPricesHtmlResponse = null;
			private $apiWhitelistedIpAddresses = ['165.22.81.95'];
			private $isRefreshing = false;
			public $redirectBtnText = 'I have sent the payment';
			public $cryptoAddressLabelTxt = 'Send transaction to this address: ';
			public $cryptoAmountLabelTxt = 'Send this exact amount:';
			public $paymentDataErrorTxt = 'Failed to get crypto payment data.';
			
			public function __construct()
			{
				global $migpayments;
			
			 
				$this->id                 	= 'migpaymentspayments';
				$this->mainplugin_url 		= admin_url("plugin-install.php?tab=search&type=term&s=MigPayments");
				$this->method_title       	= __( 'Migpayments', MIGPAYMENTSWC );
				$this->method_description  	= __( "Supports BTC,ETH, USDT", MIGPAYMENTSWC ) . '</b><br>';
				$this->supports 			= ['products'];
				$this->has_fields = true;
				$this->icon = apply_filters('woocommerce'. $this->id.'icon', plugins_url("/assets/img/currencies.png", __FILE__)); //Logo on Checkout Page
				$this->fiatCurrency = get_woocommerce_currency();
		 
				if (class_exists('migpaymentsclass') && defined('MIGPAYMENTS')  && is_object($migpayments))
				{
					$this->cryptoCurrencies			= $migpayments->cryptoCurrencies(); 	// All Coins
					$this->languages			= $migpayments->languages(); 		// All Languages
					$this->url		= MIGPAYMENTS_ADMIN.MIGPAYMENTS."settings";
					$this->url2		= MIGPAYMENTS_ADMIN.MIGPAYMENTS."payments&s=migpaymentswoocommerce";
					$this->url3		= MIGPAYMENTS_ADMIN.MIGPAYMENTS; 
				}
				 
				 if(isset($_REQUEST['wc-ajax'])){
					 $this->isRefreshing = true;
					 
				 }
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
					 
						if(isset($this->cryptoCurrencies[$currencyCode]))
							$availableCurrencies[$currencyCode] = $this->cryptoCurrencies[$currencyCode];
					}
					
					if(count($availableCurrencies))
						$this->cryptoCurrencies = $availableCurrencies;
				}
 
				if((WC()->cart && ((bool)!$this->isRefreshing && !$this->cryptoPricesHtmlResponse  || $this->showCryptoPrices)))
					$this->cryptoPricesHtmlResponse  = WC_Migpayments_Service::getCryptoPricesHtml(WC()->cart->get_total(false), $this->cryptoCurrencies, 'EUR', $this->get_option('api_token'), $this->isSandbox );
				
 
	
				//generate payment data
				// add_action( 'woocommerce_thankyou_'.$this->id, array( $this, 'getPaymentData' ) );

				//update payment options(woocmmerce settings - payments)
				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

				//payment confirmed webhook
				add_action( 'woocommerce_api_crypto-payment-confirmed', array( $this, 'paymentConfirmedWebhook' ) );
				add_action( 'woocommerce_api_crypto-partial-payment', array( $this, 'partialPaymentWebhook' ) );
			 
				return true;
			}
			 

			 
			public function paymentConfirmedWebhook(){
			 
				if(!in_array($_SERVER['REMOTE_ADDR'], $this->apiWhitelistedIpAddresses)){
					wp_send_json_error( [ 'messages' => ['Forbidden' ]]);
				}
				
				$order = wc_get_order( $_GET['id'] );
				$order->update_status('completed', __('Order payment completed.', MIGPAYMENTSWC));
				$order->payment_complete();

				update_post_meta( $order->get_id(), '_migpayments_worder_crypto_payment_status', 'Completed');
 
			
				wp_send_json('success');
			}

			public function partialPaymentWebhook(){
			  	
				if(!in_array($_SERVER['REMOTE_ADDR'], $this->apiWhitelistedIpAddresses)){
					wp_send_json_error( [ 'messages' => ['Forbidden' ]]);
				}

				$data = json_decode(file_get_contents('php://input'), true);

				$order = wc_get_order( $_GET['id'] );
				$order->add_order_note('Partial crypto payment received with amount of '.$data['amount'] . ' '.$data['crypto_currency']. '.', true);

				update_post_meta( $order->get_id(), '_migpayments_worder_crypto_payment_status', 'Partially paid');

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
				$this->redirectBtnText      =$this->get_option( 'redirect_button_txt' ) && $this->get_option( 'redirect_button_txt' ) != '' ? $this->get_option( 'redirect_button_txt' ) : $this->redirectBtnText;
				$this->cryptoAddressLabelTxt      =$this->get_option( 'crypto_address_label_txt' ) && $this->get_option( 'crypto_address_label_txt' ) != '' ? $this->get_option( 'crypto_address_label_txt' ) : $this->cryptoAddressLabelTxt;
				$this->cryptoAmountLabelTxt      =$this->get_option( 'crypto_amount_label_txt' ) && $this->get_option( 'crypto_amount_label_txt' ) != '' ? $this->get_option( 'crypto_amount_label_txt' ) : $this->cryptoAmountLabelTxt;
				$this->paymentDataErrorTxt      =$this->get_option( 'payment_data_error_txt' ) && $this->get_option( 'payment_data_error_txt' ) != '' ? $this->get_option( 'payment_data_error_txt' ) : $this->paymentDataErrorTxt;
				
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
					'redirect_button_txt'			=> array(
						'title'       	=> __( 'Redirect Button Text', MIGPAYMENTSWC ),
						'type'        	=> 'text',
						'default'     	=> __( 'I have sent the payment', MIGPAYMENTSWC ),
						'description' 	=> __( 'Payment success redirect button  text placed on payment instructions page.', MIGPAYMENTSWC )
					),
					'crypto_address_label_txt'			=> array(
						'title'       	=> __( 'Crypto Address Label Text', MIGPAYMENTSWC ),
						'type'        	=> 'text',
						'default'     	=> __( 'Send transaction to this address:', MIGPAYMENTSWC ),
						'description' 	=> __( 'Crypto address label text placed on payment instructions page.', MIGPAYMENTSWC )
					),
					'crypto_amount_label_txt'			=> array(
						'title'       	=> __( 'Crypto Amount Label Text', MIGPAYMENTSWC ),
						'type'        	=> 'text',
						'default'     	=> __( 'Send this exact amount: ', MIGPAYMENTSWC ),
						'description' 	=> __( 'Crypto amount label text placed on payment instructions page.', MIGPAYMENTSWC )
					),
					'payment_data_error_txt' 	=> array(
						'title'       	=> __( 'Payment Data Error Text', MIGPAYMENTSWC ),
						'type'        	=> 'textarea',
						'default'     	=> __( 'Failed to get crypto payment data.', MIGPAYMENTSWC),
						'description' 	=> __( 'Error text show on payment instructions pages', MIGPAYMENTSWC )
					),
					
					'api_token' 	=> array(
						'title'       	=> __( 'Migpayments API Token', MIGPAYMENTSWC ),
						'type'        	=> 'text',
						'default'     	=> null,
						'description' 	=> __( '', MIGPAYMENTSWC )
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
				global $migpayments;
			
				if ( $this->description ) 
					echo '<div id="wc-migpayments-payment-method-description">'. $this->description .'</div>';
				 
				echo '<fieldset id="wc-' . esc_attr( $this->id ) . '-crypto-payment-form"  style="background:transparent;">';
			
				// Add this action hook if you want your custom payment gateway to support it
				do_action( 'woocommerce_crypto_payment_form_start', $this->id );
					
			
					if(!in_array($this->fiatCurrency, $this->fiatCurrencies)){
						echo '<div class="woocommerce-error"> Crypto payment method is not available for '. $this->fiatCurrency .' shop currency.</div>';
						return;
					}  
					
					if($this->cryptoPricesHtmlResponse && !$this->cryptoPricesHtmlResponse->error && $this->cryptoPricesHtmlResponse->data)
							echo $this->cryptoPricesHtmlResponse->data;
					
					echo '<div class="form-row ">
							<label id="wc-migpayments-crypto-currency-select-label">Choose Crypto Currency<span class="required">*</span></label>
							<select id="wc-migpayments-crypto-currency-select" name="crypto_currency">';
					
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
 	
					update_post_meta( $orderId, '_migpayments_worder_crypto_payment_status', 'Pending');
					update_post_meta( $orderId, '_migpayments_worder_crypto_currency_code', 	    $_POST['crypto_currency'] );
					update_post_meta( $orderId, '_migpayments_worder_orderid', 	    $orderId );
					update_post_meta( $orderId, '_migpayments_worder_userid', 	    $userID );
					update_post_meta( $orderId, '_migpayments_worder_createtime',   gmdate("c") );

					update_post_meta( $orderId, '_migpayments_worder_orderpage',     $orderpage );
					update_post_meta( $orderId, '_migpayments_worder_created',      gmdate("d M Y, H:i") );
		
				}
			 
				// Empty cart
				WC()->cart->empty_cart();
				
			 
				$response = $this->getPaymentData($orderId);
				
				$cryptoAddress = null;
				$cryptoAmount = null;
				$currencyCode = null;

				if(!$response->error){
					if(isset($response->data['cryptoAddress'])){
						$cryptoAddress = $response->data['cryptoAddress'];
					}
					if(isset($response->data['calculatedAmount'])){
						$cryptoAmount = $response->data['calculatedAmount'];
					}
					if(isset($response->data['currency'])){
						$currencyCode = $response->data['currency'];
					}
				}

				update_post_meta( $orderId, '_migpayments_worder_crypto_amount', $cryptoAmount );
				update_post_meta( $orderId, '_migpayments_worder_crypto_address', $cryptoAddress );

				return array(
					'result' => 'success',
					'redirect' => site_url('migpayments-payment-instructions?orderId='.$orderId.'&address='.$cryptoAddress.'&currency='. $currencyCode .'&amount='.$cryptoAmount.'&success_url='. $this->get_return_url($order))
				);
				
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
				$fiatCurrencyCode = (true === version_compare(WOOCOMMERCE_VERSION, '3.0', '<')) ? $order->order_currency : $order->get_currency();
				$orderTotal    = (true === version_compare(WOOCOMMERCE_VERSION, '3.0', '<')) ? $order->orderTotal    : $order->get_total();
				$cryptoCurrencyCode = get_post_meta( $orderId, '_migpayments_worder_crypto_currency_code', true );
					 
				$user = $order->get_user();

				$orderData = [
					'customer_email_address' =>  $user ? $user->user_email : null,
					'customer_username' =>  $user ? $user->user_login . '('. $user->display_name . ')' : null,
				 
					
				];

				$response  = WC_Migpayments_Service::getPaymentData($orderTotal, $cryptoCurrencyCode, $fiatCurrencyCode, $orderId, $this->apiToken, $this->isSandbox , $orderData );

				return  $response;
			}
			
			
		}
		// end class WC_Gateway_MigPayments
 	}
}