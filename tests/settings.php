<?php
/** Credential regressions on an isolated local WordPress; no bank HTTP is permitted. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! defined( 'Y2KWEBS_BAC_LOCAL_TEST' ) ) { exit( 'Local tests only.' ); }
$checks = 0;
$assert = static function ( $ok, $message ) use ( &$checks ) { if ( ! $ok ) { throw new RuntimeException( $message ); } ++$checks; };
$option = Y2KWEBS_BAC_Settings::OPTION;
$old_settings = get_option( $option, null ); $old_secrets = get_option( Y2KWEBS_BAC_Secrets::OPTION, null );
$old_user = get_current_user_id(); $old_post = $_POST; $http_calls = 0;
$block = static function () use ( &$http_calls ) { ++$http_calls; return new WP_Error( 'settings_test_http_blocked', 'External HTTP blocked.' ); };
add_filter( 'pre_http_request', $block, PHP_INT_MAX );
try {
	wp_set_current_user( 1 );
	delete_option( $option ); delete_option( Y2KWEBS_BAC_Secrets::OPTION );
	$fresh = new Y2KWEBS_BAC_Gateway();
	$assert( 'Tarjeta de crédito o débito' === $fresh->title && 'Pago seguro procesado por PowerTranz.' === $fresh->description, 'Fresh installation uses public checkout texts, without demo or private-test labels.' );
	$assert( 'no' === $fresh->enabled && 'staging' === $fresh->get_option( 'environment' ) && 'administrators' === $fresh->get_option( 'availability_mode' ), 'Fresh installation remains disabled, staging and administrator-only.' );
	$crc = 'synthetic-crc-+\\"/='; $usd = 'synthetic-usd-+\\"/=';
	$settings = array( 'enabled' => 'yes', 'environment' => 'staging', 'availability_mode' => 'administrators', 'crc_id' => 'crc-test-id', 'usd_id' => 'usd-test-id', 'crc_password' => $crc, 'usd_password' => $usd );
	update_option( $option, $settings, false );
	$saved = get_option( $option ); $sealed = get_option( Y2KWEBS_BAC_Secrets::OPTION );
	$assert( '' === $saved['crc_password'] && '' === $saved['usd_password'], 'Gateway settings never persist plaintext passwords.' );
	$assert( false === strpos( $sealed, $crc ) && false === strpos( $sealed, $usd ), 'Vault contains no plaintext credential.' );
	$assert( $crc === Y2KWEBS_BAC_Secrets::get( 'crc_password' ) && $usd === Y2KWEBS_BAC_Secrets::get( 'usd_password' ), 'Encryption preserves exact credential bytes.' );
	global $wpdb;
	$autoload = $wpdb->get_var( $wpdb->prepare( "SELECT autoload FROM {$wpdb->options} WHERE option_name=%s", Y2KWEBS_BAC_Secrets::OPTION ) );
	$assert( in_array( $autoload, array( 'no', 'off', 'auto-off' ), true ), 'Vault is not autoloaded.' );
	$assert( Y2KWEBS_BAC_Secrets::seal( array( 'crc_password' => $crc ) ) !== Y2KWEBS_BAC_Secrets::seal( array( 'crc_password' => $crc ) ), 'Independent seals use fresh nonces.' );
	$config = new Y2KWEBS_BAC_Currency_Config( $saved );
	$assert( $config->account_for( 'CRC' )['password'] === $crc && $config->account_for( 'USD' )['password'] === $usd, 'Currency accounts resolve decrypted administrator credentials without constants.' );
	$settings['crc_password'] = ''; $settings['usd_password'] = ''; $settings['title'] = 'Synthetic title';
	update_option( $option, $settings, false );
	$assert( $sealed === get_option( Y2KWEBS_BAC_Secrets::OPTION ), 'Blank fields preserve both saved passwords.' );
	$settings['usd_password'] = 'synthetic-usd-replacement'; update_option( $option, $settings, false );
	$assert( $crc === Y2KWEBS_BAC_Secrets::get( 'crc_password' ) && 'synthetic-usd-replacement' === Y2KWEBS_BAC_Secrets::get( 'usd_password' ), 'Replacing USD preserves CRC.' );
	$gateway = new Y2KWEBS_BAC_Gateway();
	$html = $gateway->generate_password_html( 'crc_password', $gateway->form_fields['crc_password'] );
	$assert( false !== strpos( $html, 'value=""' ) && false === strpos( $html, $crc ) && false === strpos( $html, get_option( Y2KWEBS_BAC_Secrets::OPTION ) ), 'Administrator HTML never preloads plaintext or ciphertext.' );
	$server = rest_get_server();
	$request = new WP_REST_Request( 'GET', '/wc/v3/payment_gateways/y2kwebs_bac' );
	$response = $server->dispatch( $request ); $data = $response->get_data();
	$assert( 200 === $response->get_status() && '' === $data['settings']['crc_password']['value'] && '' === $data['settings']['usd_password']['value'], 'Authenticated WooCommerce GET returns no passwords.' );
	$request = new WP_REST_Request( 'PUT', '/wc/v3/payment_gateways/y2kwebs_bac' );
	$request->set_body_params( array( 'settings' => array( 'crc_password' => '', 'usd_password' => 'synthetic-api-replacement' ) ) );
	$response = $server->dispatch( $request );
	$assert( 200 === $response->get_status() && false === strpos( wp_json_encode( $response->get_data() ), 'synthetic-api-replacement' ), 'WooCommerce PUT does not echo the submitted password.' );
	$assert( $crc === Y2KWEBS_BAC_Secrets::get( 'crc_password' ) && 'synthetic-api-replacement' === Y2KWEBS_BAC_Secrets::get( 'usd_password' ), 'WooCommerce API uses the same encrypted storage and blank preservation.' );
	$before = get_option( Y2KWEBS_BAC_Secrets::OPTION );
	wp_set_current_user( 0 );
	$response = $server->dispatch( $request );
	$assert( in_array( $response->get_status(), array( 401, 403 ), true ) && $before === get_option( Y2KWEBS_BAC_Secrets::OPTION ), 'Unauthorized API calls cannot write credentials.' );
	wp_set_current_user( 1 );
	$_POST = array( '_wpnonce' => 'invalid', 'woocommerce_y2kwebs_bac_usd_password' => 'synthetic-forged' );
	$assert( false === $gateway->process_admin_options() && $before === get_option( Y2KWEBS_BAC_Secrets::OPTION ), 'An invalid admin nonce cannot change credentials.' );
	$_POST = array( '_wpnonce' => wp_create_nonce( 'woocommerce-settings' ), 'woocommerce_y2kwebs_bac_enabled' => '1', 'woocommerce_y2kwebs_bac_environment' => 'staging', 'woocommerce_y2kwebs_bac_availability_mode' => 'administrators', 'woocommerce_y2kwebs_bac_crc_id' => 'crc-test-id', 'woocommerce_y2kwebs_bac_usd_id' => 'usd-test-id', 'woocommerce_y2kwebs_bac_crc_password' => '', 'woocommerce_y2kwebs_bac_usd_password' => wp_slash( $usd ) );
	$gateway->process_admin_options();
	$assert( $usd === Y2KWEBS_BAC_Secrets::get( 'usd_password' ) && $crc === Y2KWEBS_BAC_Secrets::get( 'crc_password' ), 'The normal settings form saves credentials with exact bytes and preserves blank CRC.' );
	$assert( '' === $gateway->settings['usd_password'], 'The gateway object is redacted after an admin save.' );
	$before = get_option( Y2KWEBS_BAC_Secrets::OPTION );
	$bad = get_option( $option ); $bad['usd_password'] = "invalid\ncredential"; update_option( $option, $bad, false );
	$assert( Y2KWEBS_BAC_Settings::has_error() && $before === get_option( Y2KWEBS_BAC_Secrets::OPTION ), 'Malformed credentials never overwrite protected values.' );
	$raw = base64_decode( substr( $before, 3 ), true ); $raw[28] = chr( ord( $raw[28] ) ^ 1 );
	update_option( Y2KWEBS_BAC_Secrets::OPTION, 'v1:' . base64_encode( $raw ), false );
	$assert( null === ( new Y2KWEBS_BAC_Currency_Config( get_option( $option ) ) )->account_for( 'USD' ), 'Tampered ciphertext cannot resolve a bank account.' );
	$bad = get_option( $option ); $bad['usd_password'] = 'synthetic-partial'; update_option( $option, $bad, false );
	$assert( Y2KWEBS_BAC_Settings::has_error(), 'Unreadable vault cannot silently discard the other currency during a partial replacement.' );
	update_option( Y2KWEBS_BAC_Secrets::OPTION, $before, false );
	$salt = static function ( $value, $scheme ) { return 'auth' === $scheme ? 'synthetic-rotated-auth-salt' : $value; };
	add_filter( 'salt', $salt, 10, 2 );
	$assert( '' === Y2KWEBS_BAC_Secrets::get( 'crc_password' ), 'Changing WordPress salts fails closed.' );
	remove_filter( 'salt', $salt );
	// Simulate this candidate's older plaintext option, not data from another plugin.
	remove_filter( 'pre_update_option_' . $option, array( 'Y2KWEBS_BAC_Settings', 'protect' ), 10 );
	$legacy = $settings; $legacy['crc_password'] = $crc; $legacy['usd_password'] = $usd;
	update_option( $option, $legacy, false );
	add_filter( 'pre_update_option_' . $option, array( 'Y2KWEBS_BAC_Settings', 'protect' ), 10, 2 );
	Y2KWEBS_BAC_Settings::migrate();
	$assert( '' === get_option( $option )['crc_password'] && '' === get_option( $option )['usd_password'] && $crc === Y2KWEBS_BAC_Secrets::get( 'crc_password' ) && $usd === Y2KWEBS_BAC_Secrets::get( 'usd_password' ), 'Migration removes earlier plaintext without changing credentials.' );
	$request = new WP_REST_Request( 'PUT', '/wc/v3/payment_gateways/y2kwebs_bac' );
	$request->set_body_params( array( 'enabled' => true, 'settings' => array( 'clear_secrets' => 'yes' ) ) );
	$response = $server->dispatch( $request );
	$assert( 200 === $response->get_status() && false === $response->get_data()['enabled'] && 'no' === get_option( $option )['enabled'] && false === get_option( Y2KWEBS_BAC_Secrets::OPTION, false ), 'Explicit deletion removes passwords and disables payments in database and API.' );
	$assert( 0 === $http_calls, 'Settings, migration and secret validation perform no external HTTP.' );
	echo "PASS: $checks administrator credential and security assertions; no bank HTTP.\n";
} finally {
	remove_filter( 'salt', $salt ?? '__return_empty_string' );
	remove_filter( 'pre_http_request', $block, PHP_INT_MAX );
	$_POST = $old_post; wp_set_current_user( $old_user );
	if ( null === $old_settings ) { delete_option( $option ); } else { update_option( $option, $old_settings, false ); }
	if ( null === $old_secrets ) { delete_option( Y2KWEBS_BAC_Secrets::OPTION ); } else { update_option( Y2KWEBS_BAC_Secrets::OPTION, $old_secrets, false ); }
}
