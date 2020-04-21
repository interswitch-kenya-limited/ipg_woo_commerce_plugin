<?php
/*
	Plugin Name:	Interswitch Pay Button WooCommerce Payment Gateway
	Plugin URI: 	https://www.interswitchgroup.com/paymentgateway
	Description: 	Interswitch Pay Button WooCommerce Payment Gateway
	Version:		1.0.0
	Author: 		Interswitch Limited
	Author URI: 	https://www.interswitchgroup.com
	License:        GPL-2.0+
	License URI:    http://www.gnu.org/licenses/gpl-2.0.txt
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


define( 'WC_IWP_PB_MAIN_FILE', __FILE__ );

define( 'WC_IWP_PB_VERSION', '1.1.0' );

function tbz_wc_iwp_pay_button_init() {

	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}

	require_once dirname( __FILE__ ) . '/includes/class-iwp-pay-button.php';


	add_filter( 'woocommerce_payment_gateways', 'tbz_wc_add_iwp_pay_button_gateway' );

}
add_action( 'plugins_loaded', 'tbz_wc_iwp_pay_button_init', 0 );


/**
* Add Settings link to the plugin entry in the plugins menu
**/
function tbz_woo_iwp_pay_button_plugin_action_links( $links ) {

    $settings_link = array(
    	'settings' => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=iwp-pay-button' ) . '" title="View Settings">Settings</a>'
    );

    return array_merge( $links, $settings_link );

}
add_filter('plugin_action_links_' . plugin_basename( __FILE__ ), 'tbz_woo_iwp_pay_button_plugin_action_links' );


/**
* Add Interswitch Pay Button Gateway to WC
**/
function tbz_wc_add_iwp_pay_button_gateway( $methods ) {

	$methods[] = 'Tbz_WC_IWP_Pay_Button_Gateway';

	return $methods;

}


/**
* Check if Interswitch merchant details is filled
**/
function tbz_wc_iwp_pay_button_testmode_notice() {

	$settings = get_option( 'woocommerce_iwp-pay-button_settings' );

	// if ( ( ! ! $settings['iwp_merchant_code'] ) ) {

	// 	echo sprintf( '<div class="update-nag" style="color:red;">You need to enter your Payment Item ID and Merchant Code <a href="%s">here</a> to be able to process payment using the Interswitch WooCommerce Payment Gateway plugin.</div>', admin_url( 'admin.php?page=wc-settings&tab=checkout&section=iwp-pay-buttonn' ) );
	// }

	// else if ( ! $settings['iwp_merchant_code'] ) {
	// 	echo sprintf( '<div class="update-nag" style="color:red;">You need to enter your Merchant Code <a href="%s">here</a> to be able to process payment using the Interswitch WooCommerce Payment Gateway plugin.</div>', admin_url( 'admin.php?page=wc-settings&tab=checkout&section=iwp-pay-button' ) );
	// }

}
add_action( 'admin_notices', 'tbz_wc_iwp_pay_button_testmode_notice' );