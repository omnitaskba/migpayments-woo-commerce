<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class WcMigpaymentsGateway_Blocks extends AbstractPaymentMethodType {

    private $gateway;
    protected $name = 'migpaymentspayments';// your payment gateway name

    public function initialize() {
        $this->settings = get_option( 'woocommerce_migpaymentspayments_settings', [] );
        $this->gateway = new WcMigpaymentsGateway();
    }

    public function is_active() {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles() {

        wp_register_script(
            'migpaymentspayments-blocks-integration',
            plugin_dir_url(__FILE__) . 'checkout.js',
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ],
            null,
            true
        );
        if( function_exists( 'wp_set_script_translations' ) ) {            
            wp_set_script_translations( 'migpaymentspayments-blocks-integration');
            
        }
        return [ 'migpaymentspayments-blocks-integration' ];
    }

    public function get_payment_method_data() {
        return [
            'title' => $this->gateway->title,
            'description' => $this->gateway->description,
            'icon' => $this->gateway->icon,
        ];
    }

}
?>