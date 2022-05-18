<?php
/*
Plugin Name: 		MigPayments WooCommerce
Plugin URI: 		https://migpayments.tech
Description: 		A crypto payment gateway
Version: 			1.0.1
Author: 			Omnitask
Author URI: 		https://migpayments.tech
*/


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
	  
      
	/*
	 *	Payment Gateway WC Class
	 */
	class WC_Gateway_MigPayments extends WC_Payment_Gateway
	{

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
			$this->method_title       	= __( 'Crypto Payment', MIGPAYMENTSWC );
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
			// if ($this->logo) $this->icon = apply_filters('woocommerce_migpaymentspayments_icon', plugins_url("/images/crypto".$this->logo.".png", __FILE__));


			 
			 
 
			return true;
	    }




	    /*
	     * 23.2
	    */
	    private function migpayments_settings()
	    {

           
            // Define user set variables
            $this->enabled          = $this->get_option( 'enabled' );
            $this->title            = $this->get_option( 'title' );
            $this->description      = $this->get_option( 'description' );
            $this->logo             = $this->get_option( 'logo' );
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

            $this->sbc_notrial      = $this->get_option( 'sbc_notrial' );
            $this->sbc_notrialtxt   = $this->get_option( 'sbc_notrialtxt' );
            $this->sbc_usedtrialtxt = $this->get_option( 'sbc_usedtrialtxt' );


            // Re-check
            if (!$this->title)                                      $this->settings["title"]            = $this->title 		   = __('Crypto Payment', MIGPAYMENTSWC);
            if (!$this->description)                                $this->settings["description"] 		= $this->description   = __('Secure, anonymous payment with virtual currency', MIGPAYMENTSWC);
            if (!isset($this->statuses[$this->ostatus]))            $this->settings["ostatus"] 			= $this->ostatus  	   = 'processing';
            if (!isset($this->statuses[$this->ostatus2]))           $this->settings["ostatus2"] 		= $this->ostatus2 	   = 'processing';
            if (!isset($this->cryptoprices[$this->cryptoprice]))    $this->settings["cryptoprice"] 		= $this->cryptoprice   = '0';
            if (!isset($this->languages[$this->deflang]))           $this->settings["deflang"] 			= $this->deflang 	   = 'en';
            if (stripos($this->redirect, "http") !== 0)             $this->settings["redirect"] 		= $this->redirect      = '';
           


            if (!is_numeric($this->logo) || !in_array($this->logo, array(0,1,2,3,4,5,6,7,8,9,10)))      $this->settings["logo"] = $this->logo = 6;
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

	}
	// end class WC_Gateway_MigPayments

 }
 
}