<?php

/*
Plugin Name: WooCommerce Resto Flash Payments 
Plugin URI: https://www.restoflash.fr
Description: Acceptez les paiements par Resto Flash
Version: 1.0.3
*/

if (!defined('WPINC')) {
	die;
}

define('RESTOFLASH_PLUGIN_VERSION', '1.0.3');

require_once('includes/class-woo-restoflash-gateway.php');
require_once('includes/restoflash-rest-api.php');

add_filter('woocommerce_payment_gateways', 'add_resto_flash_gateway');
add_action('plugins_loaded', 'init_restoflash_gateway_class');
add_action('rest_api_init', function () {
	register_rest_route('restoflash/v1', '/payment/(?P<order_id>\d+)', array(
		'methods' => 'GET',
		'callback' =>  'redirect_restoflash',
	));
});
function add_resto_flash_gateway($gateways)
{
	$gateways[] = 'Woo_Restoflash_Gateway';
	return $gateways;
}

function redirect_restoflash(WP_REST_Request $request)
{
	//get restoflash geteaway

	$order_id = $request->get_param('order_id');
	if (!isset($order_id)) {
		wp_redirect(wc_get_checkout_url());
	}
	//get restoflash gateway
	$gateways = WC()->payment_gateways->payment_gateways();
	$restoflash_gateway = $gateways['restoflash'];
	if(!isset($restoflash_gateway)){
		wp_redirect(wc_get_checkout_url());
	}

	$restoflash_gateway->handle_restoflash_payment_id($order_id);
}