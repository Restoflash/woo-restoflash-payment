<?php
/**
 * Resto Flash Payment Gateway.
 *
 * Provides a Resto Flash Payment Gateway for WooCommerce.
 *
 * @class       Woo_Restoflash_Gateway
 * @extends     WC_Payment_Gateway
 * @version     1.0.0
 * @package     WooCommerce/Classes/Payment
 */
if (!defined('ABSPATH')) {
	exit;
}
require_once('utils.php');
require_once('restoflash-rest-api.php');


function init_restoflash_gateway_class()
{
	class Woo_Restoflash_Gateway extends WC_Payment_Gateway
	{

		public function __construct()
		{
			$this->id = 'restoflash';
			$this->icon = plugin_dir_url(__FILE__) . '../assets/images/restoflash.png'; // URL to the payment gateway's icon
			$this->has_fields = false; // Indicates whether the payment form should display input fields
			$this->method_title = __('Resto Flash', 'restoflash');
			$this->method_description = __('Acceptez les paiements Resto Flash', 'restoflash');

			// Load the form fields and settings.
			$this->init_form_fields();
			$this->init_settings();

			$this->title = "Resto Flash";
			$this->description = $this->get_option('description');
			$this->enabled = $this->get_option('enabled');

			$this->supports = array(
				'refunds'
			  );

			if (is_admin()) {
				add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
			}
			add_action('woocommerce_review_order_before_submit', array($this, 'add_restoflash_one_click_infos'));
			add_action('woocommerce_thankyou_restoflash', array($this, 'thankyou_page'));
			add_action('template_redirect', array($this, 'handle_restoflash_payment'));

		}

		public function get_auth_header()
		{
			$api_login = $this->get_option('api_login');
			$api_password = $this->get_option('api_password');
			$api_auth = $api_login . ':' . $api_password;
			return base64_encode($api_auth);
		}

		public function get_restoflash_redirect_url($request_id)
		{
			$test_mode = $this->get_option('testmode');
			if ($test_mode == 'TEST') {
				$endpoint = "https://mondemo.restoflash.fr/confirm/transaction/";
			} else if ($test_mode == 'PROD') {
				$endpoint = "https://mon.restoflash.fr/confirm/transaction/";
			}
			return $endpoint . $request_id;
		}

		public function get_restoflash_api_url()
		{
			$test_mode = $this->get_option('testmode');
			if ($test_mode == 'TEST') {
				return 'https://demoapi.restoflash.fr/web/transaction/';
			} else if ($test_mode == 'PROD') {
				return 'https://api.restoflash.fr/web/transaction/';
			}
		}


		public function init_form_fields()
		{
			$this->form_fields = include 'restoflash-settings.php';
		}

		public function process_payment($order_id)
		{
			global $woocommerce;
			$order = new WC_Order($order_id);
			$customer = new WC_Customer(get_current_user_id());
			$one_click_token = $customer->get_meta('restoflash-oneclick');

			$reference = base64url_encode($order->order_key);
			$value = number_format($order->get_total(), 2, '.', '');
			$IMEI = $this->get_option('imei');
			$email = wp_get_current_user()->user_email;

				
			$redirect_back_url = add_query_arg(
					array(
						'order_id' => $order_id,
					),
					home_url('/restoflash_payment')
				);
			$redirect_back_url = base64url_encode($redirect_back_url);

			$payload = array(
				'transacTimeInMillis' => time() * 1000,
				'encodedValue' => base64url_encode($value),
				'acceptPartial' => true,
				'encodedImei' => base64url_encode($IMEI),
				'credentialToken' => $one_click_token,
				'authorizationMode' => false,
				'encodedEmail' => base64url_encode($email),
				'encodedRedirectUrl' => $redirect_back_url,
			);

			$response = post_restoflash_init_transaction($this->get_auth_header(), $this->get_restoflash_api_url(), $reference, $payload);
			if ($response['status'] == 'success') {
				$transaction_web_result = $response['transaction_web_result'];
				$request_id = $transaction_web_result->restoflashId;
				$order->update_meta_data('restoflash_request_id', $request_id);
				$order->save();
				$state = $transaction_web_result->state;
				switch ($state) {
					case 'VALIDATED':
						$this->transaction_completed($order, $transaction_web_result);
						return array(
							'result' => 'success',
							'redirect' => $this->get_return_url($order),
						);
					case 'OPEN':
						//Redirect to Resto Flash login page to complete the payment
						$restoflash_auth_url = $this->get_restoflash_redirect_url($request_id);
						return array(
							'result' => 'success',
							'redirect' => $restoflash_auth_url,
						);
					case 'REJECTED':
						$this->transaction_rejected($order, $request_id);
						wc_add_notice(__("Le serveur Resto Flash a rejeté la paiement", 'woo-restoflash-gateway'), 'error');
						return array(
							'result' => 'fail',
							'redirect' => wc_get_checkout_url(),
						);
				}
			} else {
				wc_print_notice(__($response->msg, 'woo-restoflash-gateway'), 'error');
				return array(
					'result' => 'fail',
					'redirect' => wc_get_checkout_url(),
				);
			}
		}

		public function process_refund( $order_id, $amount = null, $reason = '' ) {
			$order = new WC_Order($order_id);
			$transaction_id = $order->get_transaction_id();
			$total_amount = $order->get_total();
			$amount_to_refound = $amount;
			$encoded_imei = base64url_encode($this->get_option('imei'));
			$response = post_refound_transaction($this->get_auth_header(), $this->get_restoflash_api_url(), $transaction_id, $total_amount, $amount_to_refound, $encoded_imei);
			if ($response['status'] == 'success') {
				$transaction = $response['result'];
				if($transaction == 0) // complete refound
				{
					
					$order->add_order_note(
						sprintf(
							/* translators: 1: amount 2: transaction id */
							__( 'Remboursement sur le compte Resto Flash de l\'usager (%1$s)', 'woo-restoflash-gateway' ),
							wc_price( $amount_to_refound )
						)
					);
					$order->update_status('refounded');
				}
				else{
					
					$order->add_order_note(
						sprintf(
							/* translators: 1: amount 2: transaction id */
							__( 'Remboursement partiel sur le compte Resto Flash de l\'usager : %1$s sur %2$s', 'woo-restoflash-gateway' ),
							wc_price( $amount_to_refound ),
							wc_price( $total_amount)
						)
					);
				}
				return true;
			} else {
				$order->add_order_note(
					sprintf(
						/* translators: 1: amount 2: transaction id */
						__( 'Le remboursement Resto Flash a échoué avec l\'erreur %1$s', 'woo-restoflash-gateway' ),
						$response['msg']
					)
				);
				return new WP_Error( 'restoflash_refund_error', $response['msg'] );
			}

		  }

		public function handle_restoflash_payment()
		{
			global $wp_query;
			if ($wp_query->query_vars['name'] != 'restoflash_payment') {
				return;
			}
			if (!isset($_GET['order_id'])) {
				wp_redirect(wc_get_checkout_url());
				exit;
			}
			$order_id = $_GET['order_id'];
			$order = new WC_Order($order_id);
			if ($order->has_status('completed')) {
				wp_redirect($this->get_return_url($order));
				exit;
			}
			$request_id = $order->get_meta('restoflash_request_id');

			$response = get_restoflash_status($this->get_auth_header(), $this->get_restoflash_api_url(), $request_id);

			if ($response['status'] === 'success') {
				
				$result = $response['transaction_web_result'];
				$state = $result->state;

				switch($state){
					case 'VALIDATED':
						$this->transaction_completed($order, $response['transaction_web_result']);
						wp_redirect($this->get_return_url($order));
						break;
					case 'REJECTED':
						$this->transaction_rejected($order, $request_id);
						wc_add_notice(__("Le paiement Resto Flash a été rejeté", 'woo-restoflash-gateway'), 'error');
						wp_redirect(wc_get_checkout_url());
						break;
					case 'CANCELED':
						wc_add_notice(__("Le paiement Resto Flash a été annulé", 'woo-restoflash-gateway'), 'error');
						wp_redirect( wc_get_checkout_url());
						break;
				}	
				exit;
			} else {
				$error_msg = esc_html($response['msg']);
				$this->transaction_rejected($order, $request_id);
				wc_add_notice(__($error_msg, 'woo-restoflash-gateway'), 'error');
				wp_redirect(wc_get_checkout_url());
				exit;
			}
		}

		public function transaction_completed($order, $transaction_web_restult)
		{
			global $woocommerce;
			$restoflash_id = $order->get_meta('restoflash_request_id');
			$order->payment_complete($restoflash_id);
			$order->add_order_note(sprintf(__('Transaction Resto Flash validée : id Resto flash: %s', 'woo-restoflash-gateway'), $restoflash_id));
			if (isset($woocommerce->cart)) {
				$woocommerce->cart->empty_cart();
			}
			$customer = new WC_Customer(get_current_user_id());
			$customer->add_meta_data('restoflash-oneclick', $transaction_web_restult->credentialToken, true);
			$customer->save();
		}

		public function transaction_rejected($order, $request_id)
		{
			$order->update_status('failed', sprintf(__('le Paiement Restoflash %s a éte rejeté'), $request_id));
		}



		function add_restoflash_one_click_infos()
		{
			// get current Customer 
			$customer = new WC_Customer(get_current_user_id());
			$one_click_token = $customer->get_meta('restoflash-oneclick');
			if ($one_click_token && $one_click_token != '') {
				echo '<p><b>Le paiement Resto Flash OneClick est disponible</b></p>';
			}

		}
		public function thankyou_page($order_id)
		{
			$order = new WC_Order($order_id);
			if ($order->get_payment_method() != 'restoflash') {
				return;
			}
			wc_print_notice(__('Transaction Resto Flash validée', 'woo-restoflash-gateway'), 'success');

		}

	}


}