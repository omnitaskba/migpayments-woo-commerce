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
		// add_action( 'wp_head', 'migpayments_wc_style' );
	}

	// function migpayments_wc_style() {
	// 	wp_enqueue_style('migpayments_wc_stylesheet', WP_PLUGIN_URL. '/assets/css/migpayments_style.css',false,'1.0',"all");
	//  }
	
 
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
		
			private $cryptoCurrencies         = array('BTC' => 'BTC', 'ETH' => 'ETH', 'USDT' => 'USDT');
			private $mainplugin_url     = '';
			private $url                = '';
			private $url3               = '';
			private $qrCodeWidthPx         = 200;
			private $cryptoPricesHtmlResponse = null;
			public function __construct()
			{
				global $migpayments;


				$this->id                 	= 'migpaymentspayments';
				$this->mainplugin_url 		= admin_url("plugin-install.php?tab=search&type=term&s=MigPayments");
				$this->method_title       	= __( 'Migpayments', MIGPAYMENTSWC );
				$this->method_description  	= __( "Supports BTC,ETH, USDT", MIGPAYMENTSWC ) . '</b><br>';
				$this->supports 			= array( 'products',
											'subscriptions',
											'subscription_suspension',
											'subscription_reactivation',
											'multiple_subscriptions'
										);
											// Logo on Checkout Page
				$this->icon = apply_filters('woocommerce_migpaymentspayments_icon', plugins_url("/assets/img/logo.png", __FILE__));
			 
				$this->cryptoPricesHtmlResponse  = WC_Migpayments_Service::getCryptoPricesHtml(WC()->cart->get_total(false), $this->cryptoCurrencies, 'EUR', $this->get_option('api_token') );
				
				$enabled = ((MIGPAYMENTSWC_AFFILIATE_KEY=='migpayments' && $this->get_option('enabled')==='') || $this->get_option('enabled') == 'yes' || $this->get_option('enabled') == '1' || $this->get_option('enabled') === true) ? true : false;
	
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
	
	
				// Load the settings.
				$this->init_form_fields();
				$this->init_settings();
				$this->migpayments_settings();
	
				//generate payment data
				add_action( 'woocommerce_thankyou_migpaymentspayments', array( $this, 'getPaymentData' ) );

				//update payment options(woocmmerce settings - payments)
				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

				//payment confirmed webhook
				add_action( 'woocommerce_api_crypto-payment-confirmed', array( $this, 'paymentConfirmedWebhook' ) );

				return true;
			}

	
			public function paymentConfirmedWebhook(){
			
				$order = wc_get_order( $_GET['id'] );
				$order->update_status('completed', __('Order payment completed.', MIGPAYMENTSWC));
				$order->payment_complete();
			
				wp_send_json('success');
			}

			
			private function migpayments_settings()
			{

				// Define user set variables
				$this->enabled          = $this->get_option( 'enabled' );
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
					'enabled'		=> array(
						'title'   	  	=> __( 'Enable/Disable', MIGPAYMENTSWC ),
						'type'    	  	=> 'checkbox',
						'default'	  	=> (MIGPAYMENTSWC_AFFILIATE_KEY=='migpayments'?'yes':'no'),
						'label'   	  	=> sprintf(__( "Enable crypto payment method in WooCommerce", MIGPAYMENTSWC ), $this->url3)
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
				// I recommend to use inique IDs, because other gateways could already use #ccNo, #expdate, #cvc
				echo '<div class="form-row form-row-first">
						<label>Choose Crypto Currency<span class="required">*</span></label>
						<select id="crypto_currency" name="crypto_currency">

							<option value="ETH">ETH</option>
							<option value="BTC">BTC</option>
							<option value="USDT">USDT</option>
						</select>
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
					
				
					$response  = WC_Migpayments_Service::getPaymentData($orderTotal, $cryptoCurrencyCode, $fiatCurrencyCode, $orderId, $this->apiToken );
					$responseHtml = '<div class="woocommerce-error">Failed to get crypto payment data.</div>';
					
					// $response['success'] = true;
					// $response['data']['cryptoAddress'] = '0x1234';
					// $response['data']['calculatedAmount'] = 123;
					if(!$response->error){
						$data = $response->data;

						update_post_meta( $orderId, '_migpayments_worder_fiat_amount', $orderTotal );
						update_post_meta( $orderId, '_migpayments_worder_crypto_amount',  $data['calculatedAmount']);


						$responseHtml = 'Crypto address:'. $data['cryptoAddress'] .'<br>';
						$responseHtml .= '<img id="migpayments-address-qr-code" style="width:'. $this->qrCodeWidthPx . 'px;" src=" '.(new QRCode)->render($data['cryptoAddress']).'" alt="QR Code" />';
						$responseHtml .= 'Amount:'. $data['calculatedAmount'] .'<br>';
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
	    
	  	 
		$htmlResponse = '<h3>Crypto Payment Info</h3>';
		$htmlResponse .= 'Crypto Currency: '. $cryptoCurrencyCode . '<br>';
		$htmlResponse .= 'Crypto Amount: '. $cryptoAmount. '<br>';
		
		echo $htmlResponse;
	   
		return;
	}
 
}