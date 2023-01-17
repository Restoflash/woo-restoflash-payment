<?php

/*
Plugin Name: Payment Gateway for Resto Flash on WooCommerce 
Plugin URI: https://www.restoflash.fr
Description: Acceptez les paiements par Resto Flash
Version: 1.0
*/

if (!defined('WPINC')) {
	die;
}

define('RESTOFLASH_PLUGIN_VERSION', '1.0.0');

require_once('includes/class-woo-restoflash-gateway.php');
require_once('includes/restoflash-rest-api.php');

add_action('plugins_loaded', 'init_restoflash_gateway_class');
add_filter('woocommerce_payment_gateways', 'add_resto_flash_gateway');
function add_resto_flash_gateway($gateways)
{
	$gateways[] = 'Woo_Restoflash_Gateway';
	return $gateways;
}

