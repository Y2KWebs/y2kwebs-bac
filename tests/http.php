<?php
/** Actual callback HTTP against the disposable web container; bank transport is simulated. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! defined( 'Y2KWEBS_BAC_LOCAL_TEST' ) ) { exit( 'Local tests only.' ); }
$old_user = get_current_user_id(); $old_post = $_POST;
$order = null;
try {
	wp_set_current_user( 1 );
	$gateway = new Y2KWEBS_BAC_Gateway();
	$order = wc_create_order(); $order->set_currency( 'USD' ); $order->set_total( '1.00' ); $order->set_payment_method( 'y2kwebs_bac' );
	$order->set_billing_first_name( 'Demo' ); $order->set_billing_last_name( 'HTTP' ); $order->set_billing_country( 'CR' );
	$order->set_billing_email( 'demo@example.invalid' ); $order->set_billing_phone( '22223333' ); $order->save();
	$_POST = array( 'y2kwebs_bac_cardholder' => 'Demo HTTP', 'y2kwebs_bac_card_pan' => '4012000000020006', 'y2kwebs_bac_card_expiry' => '12/99', 'y2kwebs_bac_card_cvv' => '323' );
	$result = $gateway->process_payment( $order->get_id() );
	if ( 'success' !== $result['result'] ) { throw new RuntimeException( 'HTTP fixture cannot start: ' . wp_json_encode( array( 'notices' => wc_get_notices(), 'notes' => wp_list_pluck( wc_get_order_notes( array( 'order_id' => $order->get_id() ) ), 'content' ) ) ) ); }
	$order = wc_get_order( $order->get_id() );
	$doc = new DOMDocument(); @$doc->loadHTML( $order->get_meta( '_y2kwebs_bac_redirect_data', true ) );
	$form = $doc->getElementsByTagName( 'form' )->item( 0 );
	$action = $form->getAttribute( 'action' );
	$parts = wp_parse_url( $action );
	$url = 'http://wordpress' . $parts['path'] . ( isset( $parts['query'] ) ? '?' . $parts['query'] : '' );
	$body = array(); foreach ( $doc->getElementsByTagName( 'input' ) as $input ) { $body[ $input->getAttribute( 'name' ) ] = $input->getAttribute( 'value' ); }
	$response = wp_remote_post( $url, array( 'headers' => array( 'Host' => 'localhost:8088' ), 'body' => $body, 'redirection' => 0 ) );
	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		throw new RuntimeException( 'HTTP callback rejected: ' . ( is_wp_error( $response ) ? $response->get_error_message() : wp_remote_retrieve_response_code( $response ) ) );
	}
	wp_cache_flush();
	$order = wc_get_order( $order->get_id() );
	$order->read_meta_data( true );
	$order->get_data_store()->read( $order );
	if ( ! $order->is_paid() ) { throw new RuntimeException( 'Actual HTTP callback did not complete the order: ' . $order->get_status() . '/' . $order->get_meta( '_y2kwebs_bac_status', true ) ); }
	if ( 'no-referrer' !== wp_remote_retrieve_header( $response, 'referrer-policy' ) ) { throw new RuntimeException( 'Callback missing referrer policy.' ); }
	$response = wp_remote_post( $url, array( 'headers' => array( 'Host' => 'localhost:8088' ), 'body' => $body, 'redirection' => 0 ) );
	if ( 400 !== wp_remote_retrieve_response_code( $response ) || ! wc_get_order( $order->get_id() )->is_paid() ) { throw new RuntimeException( 'Duplicate HTTP callback must be rejected without altering the paid order.' ); }
	echo "PASS: Actual HTTP callback, bank completion and duplicate rejection; bank simulated.\n";
} finally {
	$_POST = $old_post; wp_set_current_user( $old_user ); if ( $order ) { $order->delete( true ); }
}
