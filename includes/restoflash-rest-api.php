<?php
function get_restoflash_status($api_auth, $endpoint_url, $request_id)
{
	global $woocommerce;
	$service = $endpoint_url . $request_id ;
	$response = wp_remote_get(
		$service,
		array(
			'headers' => array(
				'Content-Type' => 'application/json',
				'Authorization' => 'Basic ' . $api_auth,
			),
		)
	);
	if (!is_wp_error($response) && 200 === wp_remote_retrieve_response_code($response)) {
		$response_body = json_decode(wp_remote_retrieve_body($response));

		if ($response_body->status > 0) {
			return array(
				'status' => 'error',
				'msg' => $response_body->msg,
			);
		}
		return array(
			'status' => 'success',
			'transaction_web_result' => $response_body->result,
		);
	}
	else {
		return array(
			'status' => 'error',
			'msg' => 'Bad status code ' .  wp_remote_retrieve_response_code($response),
		);
	}


}

function post_restoflash_init_transaction($api_auth, $endpoint_url, $wp_reference, $payload)
{
	$service = $endpoint_url . $wp_reference . '.json';
	$response = wp_remote_post(
		$service,
		array(
			'headers' => array(
				'Content-Type' => 'application/json',
				'Authorization' => 'Basic ' . $api_auth,
			),
			'body' => wp_json_encode($payload),
		)
	);

	// Check for successful response
	if (!is_wp_error($response) && 200 === wp_remote_retrieve_response_code($response)) {
		// Decode the response body
		$response_body = json_decode(wp_remote_retrieve_body($response));
		if ($response_body->status > 0) {
			return array(
				'status' => 'error',
				'msg' => $response_body->msg,
			);
		}
		return array(
			'status' => 'success',
			'transaction_web_result' => $response_body->result,
		);
	} else {
		return array(
			'status' => 'error',
			'msg' => 'Bad status code ' . wp_remote_retrieve_response_code($response),
		);
	}
}


function post_refound_transaction($api_auth, $endpoint_url, $transaction_id, $total_amount, $amount_to_refound, $encoded_imei)
{
	$service = $endpoint_url . $transaction_id . '/cancel.json';
	$payload = array(
		'amount' =>  number_format($total_amount, 2, '.', ''),
		'amountToCancel' => number_format($amount_to_refound, 2, '.', ''),
		'timestampInMsUTC' => time() * 1000,
		'encodedImei' => $encoded_imei,
	);
	$response = wp_remote_post(
		$service,
		array(
			'headers' => array(
				'Content-Type' => 'application/json',
				'Authorization' => 'Basic ' . $api_auth,
			),
			'body' => wp_json_encode($payload),
		)
	);

	// Check for successful response
	if (!is_wp_error($response) && 200 === wp_remote_retrieve_response_code($response)) {
		// Decode the response body
		$response_body = json_decode(wp_remote_retrieve_body($response));
		if ($response_body->status > 0) {
			return array(
				'status' => 'error',
				'msg' => $response_body->msg,
			);
		}
		return array(
			'status' => 'success',
			'result' => $response_body->result,
		);
	} else {
		return array(
			'status' => 'error',
			'msg' => 'Bad status code ' .  wp_remote_retrieve_response_code($response),
		);
	}

}