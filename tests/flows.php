<?php
/** Payment regressions on a disposable WordPress database with all bank HTTP intercepted. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! defined( 'Y2KWEBS_BAC_LOCAL_TEST' ) ) {
	exit( 'Local tests only.' );
}
$checks = 0;
$assert = static function ( $ok, $message ) use ( &$checks ) {
	if ( ! $ok ) { throw new RuntimeException( $message ); }
	++$checks;
};
$old_settings = get_option( 'woocommerce_y2kwebs_bac_settings', null );
$old_user = get_current_user_id();
$old_post = $_POST;
$orders = array();
$calls = array();
$expected = array();
$mode = 'approve';
$nested = null;
$gateway = null;
add_filter( 'pre_wp_mail', '__return_false' );
$transport = static function ( $preempt, $args, $url ) use ( &$calls, &$expected, &$mode, &$nested, &$gateway ) {
	if ( false === strpos( $url, 'ptranz.com/' ) ) { return new WP_Error( 'test_external_blocked', 'External HTTP blocked.' ); }
	$calls[] = array( 'url' => $url, 'body' => $args['body'], 'headers' => $args['headers'] );
	if ( '/sale' === substr( $url, -5 ) ) {
		$request = json_decode( $args['body'], true );
		$expected = array_intersect_key( $request, array_flip( array( 'TransactionIdentifier', 'TotalAmount', 'CurrencyCode', 'OrderIdentifier' ) ) );
		$expected['callback'] = $request['ExtendedData']['MerchantResponseUrl'];
		$body = $expected;
		unset( $body['callback'] );
		$body['IsoResponseCode'] = 'SP4';
		$body['RedirectData'] = '<form action="https://staging.ptranz.com/"></form>';
	} else {
		if ( 'timeout' === $mode ) { return new WP_Error( 'http_request_failed', 'Synthetic timeout.' ); }
		if ( 'concurrent' === $mode && is_callable( $nested ) ) { $nested(); }
		$body = $expected;
		unset( $body['callback'] );
		$body['Approved'] = true;
		$body['IsoResponseCode'] = '00';
		$body['AuthorizationCode'] = 'TEST';
		if ( 'decline' === $mode ) { $body['Approved'] = false; $body['IsoResponseCode'] = '05'; }
		if ( 'false-string' === $mode ) { $body['Approved'] = 'false'; }
		if ( 'mismatch' === $mode ) { $body['TotalAmount'] = 999; }
		if ( 'invalid-json' === $mode ) { return array( 'headers' => array(), 'body' => '{', 'response' => array( 'code' => 200 ), 'cookies' => array() ); }
	}
	return array( 'headers' => array(), 'body' => wp_json_encode( $body ), 'response' => array( 'code' => 200 ), 'cookies' => array() );
};
add_filter( 'pre_http_request', $transport, 999, 3 );
try {
	wp_set_current_user( 1 );
	$settings = array( 'enabled' => 'yes', 'availability_mode' => 'administrators', 'environment' => 'staging', 'crc_id' => 'crc-test-id', 'crc_password' => 'crc-test-password', 'usd_id' => 'usd-test-id', 'usd_password' => 'usd-test-password' );
	update_option( 'woocommerce_y2kwebs_bac_settings', $settings, false );
	$gateway = new Y2KWEBS_BAC_Gateway();
	$start = static function ( $currency = 'USD' ) use ( &$orders, &$gateway, &$expected, &$assert ) {
		$order = wc_create_order(); $orders[] = $order;
		$order->set_payment_method( 'y2kwebs_bac' ); $order->set_currency( $currency ); $order->set_total( '1.00' );
		$order->set_billing_first_name( 'Demo' ); $order->set_billing_last_name( 'Shopper' );
		$order->set_billing_country( 'CR' ); $order->set_billing_email( 'demo@example.invalid' ); $order->set_billing_phone( '22223333' ); $order->save();
		$_POST = array( 'y2kwebs_bac_cardholder' => 'Demo Shopper', 'y2kwebs_bac_card_pan' => '4012000000020006', 'y2kwebs_bac_card_expiry' => '12/99', 'y2kwebs_bac_card_cvv' => '323' );
		$assert( 'success' === $gateway->process_payment( $order->get_id() )['result'], 'Synthetic sale starts.' );
		parse_str( wp_parse_url( $expected['callback'], PHP_URL_QUERY ), $query );
		return array( wc_get_order( $order->get_id() ), $query );
	};
	$post = static function ( $status = 'Y', $token = 'YWJj+/==' ) use ( &$expected ) {
		$response = $expected; unset( $response['callback'] );
		$response['RiskManagement'] = array( 'ThreeDSecure' => array( 'AuthenticationStatus' => $status ) );
		return array( 'Response' => wp_json_encode( $response ), 'SpiToken' => $token );
	};
	foreach ( array( 'Y', 'A' ) as $status ) {
		list( $order, $query ) = $start( 'A' === $status ? 'CRC' : 'USD' );
		$count = count( $calls );
		$assert( 'success' === $gateway->process_payment( $order->get_id() )['result'] && count( $calls ) === $count, 'Active authentication reuses the attempt.' );
		$result = $gateway->process_callback( $query, $post( $status ) );
		$assert( ! is_wp_error( $result ) && wc_get_order( $order->get_id() )->is_paid(), 'Allowed 3DS and bank approval pay the order.' );
		$last = end( $calls );
		$assert( 'YWJj+/==' === json_decode( $last['body'], true ) && ! isset( $last['headers']['PowerTranz-PowerTranzId'] ), 'Token bytes retained; no merchant credentials on payment.' );
		$count = count( $calls );
		$gateway->process_callback( $query, $post( $status ) );
		$gateway->process_payment( $order->get_id() );
		$assert( count( $calls ) === $count, 'Repeated callback and paid checkout cannot send another request.' );
		$meta = wp_json_encode( wc_get_order( $order->get_id() )->get_meta_data() );
		$assert( false === strpos( $meta, 'YWJj+/==' ) && false === strpos( $meta, '4012000000020006' ), 'No token or PAN stored.' );
	}
	foreach ( array( 'N', 'R', 'U', '', 'Z' ) as $status ) {
		list( $order, $query ) = $start(); $count = count( $calls );
		$gateway->process_callback( $query, $post( $status ) );
		$assert( count( $calls ) === $count && 'failed' === wc_get_order( $order->get_id() )->get_status(), 'Disallowed 3DS never calls payment.' );
	}
	$settings['allow_auth_u'] = 'yes'; update_option( 'woocommerce_y2kwebs_bac_settings', $settings, false ); $gateway = new Y2KWEBS_BAC_Gateway();
	list( $order, $query ) = $start(); $gateway->process_callback( $query, $post( 'U' ) );
	$assert( wc_get_order( $order->get_id() )->is_paid(), 'U requires an explicit opt-in.' );
	$settings['allow_auth_u'] = 'no'; update_option( 'woocommerce_y2kwebs_bac_settings', $settings, false ); $gateway = new Y2KWEBS_BAC_Gateway();
	foreach ( array( 'state', 'key', 'order_id' ) as $field ) {
		list( $order, $query ) = $start(); $query[ $field ] = 'forged'; $count = count( $calls );
		$assert( is_wp_error( $gateway->process_callback( $query, $post() ) ) && count( $calls ) === $count, 'Forged callback rejected before HTTP.' );
	}
	foreach ( array( " abc ", "abc\ndef", '<b>abc</b>', 'abc%41', array( 'token' ) ) as $token ) {
		list( $order, $query ) = $start(); $count = count( $calls );
		$assert( is_wp_error( $gateway->process_callback( $query, $post( 'Y', $token ) ) ) && count( $calls ) === $count, 'Altered or nonscalar tokens rejected.' );
	}
	foreach ( array( 'TransactionIdentifier', 'OrderIdentifier', 'CurrencyCode', 'TotalAmount' ) as $field ) {
		list( $order, $query ) = $start(); $data = $post(); $response = json_decode( $data['Response'], true ); $response[ $field ] = 'TotalAmount' === $field ? 999 : 'forged'; $data['Response'] = wp_json_encode( $response ); $count = count( $calls );
		$assert( is_wp_error( $gateway->process_callback( $query, $data ) ) && count( $calls ) === $count, 'Mismatched callback cannot send a financial request.' );
	}
	list( $order, $query ) = $start(); $order->update_meta_data( '_y2kwebs_bac_started_at', time() - 301 ); $order->save(); $count = count( $calls );
	$gateway->process_callback( $query, $post() );
	$assert( count( $calls ) === $count && 'failed' === wc_get_order( $order->get_id() )->get_status(), 'Expired callback never sends payment.' );
	foreach ( array( 'timeout', 'invalid-json', 'mismatch', 'false-string' ) as $mode ) {
		list( $order, $query ) = $start(); $data = $post();
		$gateway->process_callback( $query, $data );
		$assert( 'on-hold' === wc_get_order( $order->get_id() )->get_status() && 'unknown' === wc_get_order( $order->get_id() )->get_meta( '_y2kwebs_bac_status', true ), 'Uncertain financial result is held for reconciliation.' );
		$count = count( $calls ); $gateway->process_callback( $query, $data ); $gateway->process_payment( $order->get_id() );
		$assert( count( $calls ) === $count, 'Uncertain result prevents all repeated financial requests.' );
	}
	$mode = 'approve'; list( $order, $query ) = $start();
	$order->update_meta_data( '_y2kwebs_bac_status', 'start_failed' ); $order->save();
	$deny = static function ( $allowed, $candidate ) use ( &$assert, $order ) {
		$assert( true === $allowed && $candidate instanceof WC_Order && $candidate->get_id() === $order->get_id(), 'Merchant policy receives both the decision and the validated order.' );
		return new WP_Error( 'pilot_limit', 'Synthetic merchant policy.' );
	};
	add_filter( 'y2kwebs_bac_can_start_payment', $deny, 10, 2 ); $count = count( $calls );
	$assert( 'failure' === $gateway->process_payment( $order->get_id() )['result'] && count( $calls ) === $count, 'Merchant policy cannot start an unauthorized request.' );
	remove_filter( 'y2kwebs_bac_can_start_payment', $deny );
	$mode = 'decline'; list( $order, $query ) = $start(); $gateway->process_callback( $query, $post() );
	$assert( 'failed' === wc_get_order( $order->get_id() )->get_status(), 'Correlated definitive decline fails the order.' );
	$mode = 'concurrent'; list( $order, $query ) = $start(); $data = $post();
	$nested = static function () use ( &$gateway, $query, $data, &$assert, $order ) {
		$result = $gateway->process_callback( $query, $data );
		$assert( is_wp_error( $result ) && 409 === $result->get_error_data(), 'Concurrent callback cannot acquire the order lock.' );
		$assert( 'failure' === $gateway->process_payment( $order->get_id() )['result'], 'Concurrent checkout cannot acquire the order lock.' );
	};
	$count = count( $calls ); $gateway->process_callback( $query, $data );
	$assert( count( $calls ) === $count + 1 && wc_get_order( $order->get_id() )->is_paid(), 'Concurrent processing makes exactly one payment call.' );
	echo "PASS: $checks payment flow and security assertions; all bank HTTP simulated.\n";
} finally {
	remove_filter( 'pre_http_request', $transport, 999 ); remove_filter( 'pre_wp_mail', '__return_false' );
	$_POST = $old_post; wp_set_current_user( $old_user );
	foreach ( $orders as $order ) { wc_get_order( $order->get_id() )->delete( true ); }
	if ( null === $old_settings ) { delete_option( 'woocommerce_y2kwebs_bac_settings' ); } else { update_option( 'woocommerce_y2kwebs_bac_settings', $old_settings, false ); }
	wc_clear_notices();
}
