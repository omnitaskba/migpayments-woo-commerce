<?php
/*
Plugin Name: 		MigPayments WooCommerce
Plugin URI: 		https://migpayments.tech
Description: 		A crypto payment gateway
Version: 			1.0.1
Author: 			Omnitask
Author URI: 		https://migpayments.tech
*/
require_once('src/WC_Migpayments_Library.php');

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
	}


 
	/*
	 *	2.
	*/
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

 
 /*
  *	4. Plugin Load
  */
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
		private $apiToken = null;
		private $payments           = array();
		private $languages          = array();
		private $coin_names         = array('BTC' => 'BTC', 'ETH' => 'ETH', 'USDT' => 'USDT');
		private $statuses           = array('processing' => 'Processing Payment', 'on-hold' => 'On Hold', 'completed' => 'Completed');
		private $cryptoprices        = array();
		private $showhidemenu       = array('show' => 'Show Menu', 'hide' => 'Hide Menu');
		private $mainplugin_url     = '';
		private $url                = '';
		private $url3               = '';

		public  $sbc_notrial	    =  0;
		public  $sbc_notrialtxt     = '';
		public  $sbc_usedtrialtxt   = '';

		private $logo               =  0;
		private $emultiplier        = '';
		private $ostatus            = '';
		private $ostatus2           = '';
		private $cryptoprice        = '';
		private $deflang            = '';
		private $defcoin            = '';
		private $iconwidth          = '';

		private $qrcodesize         = '';
		private $langmenu           = '';
		private $redirect           = '';



		/*
		 * 23.1
		*/
	    public function __construct()
	    {
	    	global $migpayments;


			$this->id                 	= 'migpaymentspayments';
			$this->mainplugin_url 		= admin_url("plugin-install.php?tab=search&type=term&s=MigPayments");
			$this->method_title       	= __( 'Migpayments', MIGPAYMENTSWC );
			$this->method_description  	= "";
 			$this->supports 			= array( 'products',
							               'subscriptions',
							               'subscription_suspension',
							               'subscription_reactivation',
							               'multiple_subscriptions'
							          );
		   
			$enabled = ((MIGPAYMENTSWC_AFFILIATE_KEY=='migpayments' && $this->get_option('enabled')==='') || $this->get_option('enabled') == 'yes' || $this->get_option('enabled') == '1' || $this->get_option('enabled') === true) ? true : false;
 
			if (class_exists('migpaymentsclass') && defined('MIGPAYMENTS') && defined('MIGPAYMENTS_ADMIN') && is_object($migpayments))
			{
				$this->payments 			= $migpayments->payments(); 		// Activated Payments
					$this->coin_names			= $migpayments->coin_names(); 	// All Coins
					$this->languages			= $migpayments->languages(); 		// All Languages

				$this->url		= MIGPAYMENTS_ADMIN.MIGPAYMENTS."settings";
				$this->url2		= MIGPAYMENTS_ADMIN.MIGPAYMENTS."payments&s=migpaymentswoocommerce";
				$this->url3		= MIGPAYMENTS_ADMIN.MIGPAYMENTS;
				$this->cointxt 	= (implode(", ", $this->payments)) ? implode(", ", $this->payments) : __( '- Please setup -', MIGPAYMENTSWC );
			}
			else
			{
			 
				$this->url		= $this->mainplugin_url;
				$this->url2		= $this->url;
				$this->url3		= $this->url;
				$this->cointxt 	= '<b>'.__( 'Please install MigPayments Gateway WP Plugin', MIGPAYMENTSWC ).' &#187;</b>';

			}

			$this->method_description  .= "<b>" . __( "Supports BTC,ETH, USDT", MIGPAYMENTSWC ) . '</b><br>';
			 
			$this->cryptoprices = array( __( "Original Price only", MIGPAYMENTSWC ) );
			foreach ($this->coin_names as $k => $v) $this->cryptoprices[$k] = sprintf(__( "Fiat + %s", MIGPAYMENTSWC ), ucwords($v));

			foreach ($this->coin_names as $k => $v)
			    foreach ($this->coin_names as $k2 => $v2)
			         if ($k != $k2) $this->cryptoprices[$k."_".$k2] = sprintf(__( "Fiat + %s + %s", MIGPAYMENTSWC ), ucwords($v), ucwords($v2));


 
			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();
			$this->migpayments_settings();

			// Logo on Checkout Page
			$this->icon = apply_filters('woocommerce_migpaymentspayments_icon', plugins_url("/assets/img/logo.png", __FILE__));

			// Hooks

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
		 
			update_option('webhook_debug', $_GET);

			wp_send_json('success');
		}

		
	    private function migpayments_settings()
	    {

           
            // Define user set variables
            $this->enabled          = $this->get_option( 'enabled' );
            $this->apiToken          = $this->get_option( 'api_token' );
            $this->title            = $this->get_option( 'title' );
            $this->description      = $this->get_option( 'description' );
           
            $this->emultiplier      = trim(str_replace(array("%", ","), array("", "."), $this->get_option( 'emultiplier' )));
            $this->ostatus          = $this->get_option( 'ostatus' );
            $this->ostatus2         = $this->get_option( 'ostatus2' );
            $this->cryptoprice      = $this->get_option( 'cryptoprice' );
            $this->deflang          = $this->get_option( 'deflang' );
            $this->defcoin          = $this->get_option( 'defcoin' );
            $this->iconwidth        = trim(str_replace("px", "", $this->get_option( 'iconwidth' )));

            $this->customtext       = $this->get_option( 'customtext' );
            $this->qrcodesize       = trim(str_replace("px", "", $this->get_option( 'qrcodesize' )));
            $this->langmenu         = $this->get_option( 'langmenu' );
            $this->redirect         = $this->get_option( 'redirect' );
 

           
            if (!$this->emultiplier || !is_numeric($this->emultiplier) || $this->emultiplier < 0.01)    $this->emultiplier 	= 1;
            if (!is_numeric($this->iconwidth) || $this->iconwidth < 30 || $this->iconwidth > 250)       $this->iconwidth 	= 60;
            if (!is_numeric($this->qrcodesize) || $this->qrcodesize < 0 || $this->qrcodesize > 500)     $this->qrcodesize 	= 200;

            if ($this->defcoin && $this->payments && !isset($this->payments[$this->defcoin]))           $this->defcoin = key($this->payments);
            elseif (!$this->payments)                                                                   $this->defcoin = '';
            elseif (!$this->defcoin)                                                                    $this->defcoin = key($this->payments);

            if (!isset($this->showhidemenu[$this->langmenu])) 	$this->langmenu     = 'show';
            if ($this->langmenu == 'hide') define("CRYPTOBOX_LANGUAGE_HTMLID_IGNORE", TRUE);


            return true;
	    }


	    
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
			
            );

	    	return true;
	    }
		
		public function payment_fields() {
 
			 
			if ( $this->description ) {
				echo wpautop( wp_kses_post( $this->description ) );
			}
		 
			// I will echo() the form, but you can close PHP tags and print it directly in HTML
			echo '<fieldset id="wc-' . esc_attr( $this->id ) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';
		 
			// Add this action hook if you want your custom payment gateway to support it
			do_action( 'woocommerce_crypto_payment_form_start', $this->id );
		 
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


    /*
     * 23.5 Forward to WC Checkout Page
     */
    public function process_payment( $orderId )
    {
       
        // New Order
        $order = new WC_Order( $orderId );

        $orderId    = (true === version_compare(WOOCOMMERCE_VERSION, '3.0', '<')) ? $order->id          : $order->get_id();
        $userID      = (true === version_compare(WOOCOMMERCE_VERSION, '3.0', '<')) ? $order->user_id     : $order->get_user_id();
        $orderTotal = (true === version_compare(WOOCOMMERCE_VERSION, '3.0', '<')) ? $order->orderTotal : $order->get_total();

        // Mark as pending (we're awaiting the payment)
        $order->update_status('pending', __('Awaiting payment notification from MigPayments', MIGPAYMENTSWC));

	 
        // Payment Page
        $payment_link = $this->get_return_url($order);
  
        $total = ($orderTotal >= 1000 ? number_format($orderTotal) : $orderTotal);
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
 
        
        // Remove cart
        WC()->cart->empty_cart();

        // Return redirect
        return array(
            'result' 	=> 'success',
            'redirect'	=> $payment_link
        );
    }

 
    public function getPaymentData( $orderId )
	{
		global $migpayments;

		$order = new WC_Order( $orderId );

		$orderId       = (true === version_compare(WOOCOMMERCE_VERSION, '3.0', '<')) ? $order->id             : $order->get_id();
		$order_status   = (true === version_compare(WOOCOMMERCE_VERSION, '3.0', '<')) ? $order->status         : $order->get_status();
		$post_status    = (true === version_compare(WOOCOMMERCE_VERSION, '3.0', '<')) ? $order->post_status    : get_post_status( $orderId );
		$userID         = (true === version_compare(WOOCOMMERCE_VERSION, '3.0', '<')) ? $order->user_id        : $order->get_user_id();
		$order_currency = (true === version_compare(WOOCOMMERCE_VERSION, '3.0', '<')) ? $order->order_currency : $order->get_currency();
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
			
			$migpaymentsLibrary = WC_Migpayments_Library::create();
			$response  = $migpaymentsLibrary->getPaymentData($orderTotal, $cryptoCurrencyCode, $orderId, $this->apiToken );
			$responseHtml = '<div class="woocommerce-error">Failed to get crypto payment data.</div>';
			
			if($response['success']){
				$data = $response['body'];

				update_post_meta( $orderId, '_migpayments_worder_crypto_amount', $orderTotal );
				update_post_meta( $orderId, '_migpayments_worder_fiat_amount',  $data['calculatedAmount']);


				$responseHtml = 'Crypto address:'. $data['cryptoAddress'] .'<br>';
				$responseHtml = ' [kaya_qrcode content="My encoded content"] ';
				
				$responseHtml .= 'Amount:'. $data['calculatedAmount'] .'<br>';
			}

			echo $responseHtml;
		}
		
	    return  true;
	}

	

	}
	// end class WC_Gateway_MigPayments

 }	

  function migpayments_wc_admin_order_stats( $order )
	{
	    global $migpayments;
 
	    $order_id     = (true === version_compare(WOOCOMMERCE_VERSION, '3.0', '<')) ? $order->id          : $order->get_id();
	    

		$cryptoCurrencyCode      = get_post_meta( $order_id, '_migpayments_worder_crypto_currency_code', true );
		$cryptoAmount      = get_post_meta( $order_id, '_migpayments_worder_crypto_amount', true );
	    
	  	 
		$html = '<h3>Crypto Payment Info</h3>';

		$html .= 'Crypto Currency: '. $cryptoCurrencyCode . '<br>';
		$html .= 'Crypto Amount: '. $cryptoAmount. '<br>';
		echo $html;
	   return;
	}
 
}