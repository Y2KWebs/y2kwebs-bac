<?php
/** Local fake bank. Never distribute or deploy this file to a remote site. */
defined( 'ABSPATH' ) || exit;
if ( ! defined( 'Y2KWEBS_BAC_LOCAL_TEST' ) || ! Y2KWEBS_BAC_LOCAL_TEST ) {
	return;
}
add_filter( 'pre_wp_mail', '__return_false' );
add_action( 'y2kwebs_bac_callback_rejected', static function ( $error ) {
	$response = isset( $_POST['Response'] ) ? json_decode( wp_unslash( $_POST['Response'] ), true ) : null;
	error_log( 'LOCAL_CALLBACK_REJECTED: ' . $error->get_error_message() . ' ' . wp_json_encode( array( 'fields' => array_keys( $_POST ), 'token_length' => isset( $_POST['SpiToken'] ) ? strlen( $_POST['SpiToken'] ) : 0, 'json_valid' => is_array( $response ), 'response_fields' => is_array( $response ) ? array_keys( $response ) : array() ) ) );
} );
add_filter( 'pre_http_request', static function ( $preempt, $args, $url ) {
	if ( false === strpos( $url, 'ptranz.com/' ) ) {
		return $preempt;
	}
	$request = json_decode( $args['body'], true );
	if ( '/sale' === substr( $url, -5 ) ) {
		$token = wp_generate_password( 24, false, false );
		$response = array_intersect_key( $request, array_flip( array( 'TransactionIdentifier', 'TotalAmount', 'CurrencyCode', 'OrderIdentifier' ) ) );
		$response['RiskManagement'] = array( 'ThreeDSecure' => array( 'AuthenticationStatus' => 'Y' ) );
		set_transient( 'y2kwebs_bac_fake_' . $token, $response, 300 );
		$html = '<!doctype html><html><body><p>Simulated bank — local tests only</p><form method="post" action="' . esc_url( $request['ExtendedData']['MerchantResponseUrl'] ) . '"><input type="hidden" name="SpiToken" value="' . esc_attr( $token ) . '"><input type="hidden" name="Response" value="' . esc_attr( wp_json_encode( $response ) ) . '"><button>Approve simulated payment</button></form></body></html>';
		$response['IsoResponseCode'] = 'SP4';
		$response['RedirectData'] = $html;
	} else {
		$response = is_string( $request ) ? get_transient( 'y2kwebs_bac_fake_' . $request ) : false;
		if ( ! is_array( $response ) ) {
			return new WP_Error( 'fake_bank_missing', 'No simulated attempt.' );
		}
		$response['Approved'] = true;
		$response['IsoResponseCode'] = '00';
		$response['AuthorizationCode'] = 'LOCAL';
	}
	return array( 'headers' => array(), 'body' => wp_json_encode( $response ), 'response' => array( 'code' => 200, 'message' => 'OK' ), 'cookies' => array() );
}, 10, 3 );
