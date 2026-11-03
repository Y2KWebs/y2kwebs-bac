<?php

if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! defined( 'Y2KWEBS_BAC_LOCAL_TEST' ) ) {
	exit( 'Local tests only.' );
}

defined( 'ABSPATH' ) || exit;

function powertranz_test_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

$option_name                 = 'woocommerce_y2kwebs_bac_settings';
$previous_settings           = get_option( $option_name, null );
$previous_secrets            = get_option( Y2KWEBS_BAC_Secrets::OPTION, null );
$previous_post               = $_POST;
$previous_user_id            = get_current_user_id();
$previous_precision          = ini_get( 'serialize_precision' );
$order                       = null;
$failed_order                = null;
$authentication_failed_order = null;
$captured_requests           = array();

try {
	ini_set( 'serialize_precision', '17' );
	$codes = Y2KWEBS_BAC_Currency_Config::supported_currencies();
	powertranz_test_assert( '188' === $codes['CRC'], 'CRC must map to ISO 4217 code 188.' );
	powertranz_test_assert( '840' === $codes['USD'], 'USD must map to ISO 4217 code 840.' );
	powertranz_test_assert( null === Y2KWEBS_BAC_Currency_Config::numeric_code( 'EUR' ), 'Unsupported currencies must be rejected.' );
	powertranz_test_assert( Y2KWEBS_BAC_Authentication_Policy::can_complete( 'Y' ), '3DS status Y must be allowed.' );
	powertranz_test_assert( Y2KWEBS_BAC_Authentication_Policy::can_complete( 'A' ), '3DS status A must be allowed.' );
	powertranz_test_assert( ! Y2KWEBS_BAC_Authentication_Policy::can_complete( 'U' ), '3DS status U must be denied by default.' );
	powertranz_test_assert( Y2KWEBS_BAC_Authentication_Policy::can_complete( 'U', true ), '3DS status U may only be explicitly enabled.' );
	powertranz_test_assert( ! Y2KWEBS_BAC_Authentication_Policy::can_complete( 'N' ), '3DS status N must be denied.' );
	powertranz_test_assert( ! Y2KWEBS_BAC_Authentication_Policy::can_complete( 'R' ), '3DS status R must be denied.' );
	powertranz_test_assert( '188' === Y2KWEBS_BAC_Country_Codes::numeric( 'CR' ), 'Costa Rica must map to ISO numeric code 188.' );
	powertranz_test_assert( '724' === Y2KWEBS_BAC_Country_Codes::numeric( 'ES' ), 'Spain must map to ISO numeric code 724.' );
	powertranz_test_assert( '840' === Y2KWEBS_BAC_Country_Codes::numeric( 'US' ), 'United States must map to ISO numeric code 840.' );
	powertranz_test_assert( '826' === Y2KWEBS_BAC_Country_Codes::numeric( 'GB' ), 'United Kingdom must map to ISO numeric code 826.' );
	powertranz_test_assert( '158' === Y2KWEBS_BAC_Country_Codes::numeric( 'TW' ), 'Taiwan must map to CLDR numeric code 158.' );
	powertranz_test_assert( '983' === Y2KWEBS_BAC_Country_Codes::numeric( 'XK' ), 'WooCommerce Kosovo must map to CLDR numeric code 983.' );
	powertranz_test_assert( null === Y2KWEBS_BAC_Country_Codes::numeric( 'ZZ' ), 'Unknown country codes must fail closed.' );

	$unmapped_countries = array_filter(
		array_keys( WC()->countries->get_countries() ),
		static function ( $country_code ) {
			return null === Y2KWEBS_BAC_Country_Codes::numeric( $country_code );
		}
	);
	powertranz_test_assert(
		array() === array_values( $unmapped_countries ),
		'Every WooCommerce billing country must have a numeric PowerTranz code. Missing: ' . implode( ', ', $unmapped_countries )
	);

	$settings = array(
		'enabled'      => 'yes',
		'environment'  => 'staging',
		'title'        => 'PowerTranz test',
		'description'  => 'Test only',
		'crc_id'       => 'crc-test-id',
		'crc_password' => 'crc-test-password',
		'usd_id'       => 'usd-test-id',
		'usd_password' => 'usd-test-password',
		'allow_auth_u' => 'no',
		'availability_mode' => 'administrators',
		'debug_logging' => 'yes',
	);
	update_option( $option_name, $settings, false );

	$config      = new Y2KWEBS_BAC_Currency_Config( $settings );
	$crc_account = $config->account_for( 'CRC' );
	$usd_account = $config->account_for( 'USD' );
	powertranz_test_assert( 'crc-test-id' === $crc_account['id'], 'CRC must select the CRC account.' );
	powertranz_test_assert( 'usd-test-id' === $usd_account['id'], 'USD must select the USD account.' );
	powertranz_test_assert( null === $config->account_for( 'EUR' ), 'EUR must not resolve an account.' );

	add_filter(
		'pre_http_request',
		static function ( $preempt, $args, $url ) use ( &$captured_requests ) {
			$captured_requests[] = array( 'url' => $url, 'args' => $args );
			$request             = json_decode( $args['body'], true );

			if ( '/sale' === substr( $url, -5 ) && '4111111111111111' === $request['Source']['CardPan'] ) {
				$body = array(
					'Approved'              => false,
					'TransactionIdentifier' => $request['TransactionIdentifier'],
					'TotalAmount'           => $request['TotalAmount'],
					'CurrencyCode'          => $request['CurrencyCode'],
					'OrderIdentifier'       => $request['OrderIdentifier'],
					'IsoResponseCode'       => '3D0',
					'ResponseCode'          => '326',
					'ResponseMessage'       => '<script>alert(1)</script> 3DS rejected PAN 4333333333332222 CVV 123',
					'Errors'                => array(
						array(
							'Code'    => '57',
							'Message' => 'Missing 3DS field: PhoneNumber',
						),
						array(
							'Code'    => '38',
							'Message' => 'Invalid value 4333333333332222 token AbCdEfGhIjKlMnOpQrStUvWxYz123456',
						),
					),
					'RedirectData'          => 'forbidden-redirect-data',
					'SpiToken'              => 'forbidden-spi-token',
				);
			} elseif ( '/sale' === substr( $url, -5 ) ) {
				$body = array(
					'TransactionIdentifier' => $request['TransactionIdentifier'],
					'TotalAmount'           => $request['TotalAmount'],
					'CurrencyCode'          => $request['CurrencyCode'],
					'OrderIdentifier'       => $request['OrderIdentifier'],
					'IsoResponseCode'       => 'SP4',
					'RedirectData'          => '<form action="https://staging.ptranz.com/"></form>',
					'SpiToken'              => 'initial-test-token',
				);
			} else {
				$body = array(
					'Approved'              => true,
					'IsoResponseCode'       => '00',
					'TransactionIdentifier' => 'payment-test-id',
					'TotalAmount'           => 1,
					'CurrencyCode'          => '840',
					'OrderIdentifier'       => 'payment-test-order',
				);
			}

			return array(
				'headers'  => array(),
				'body'     => wp_json_encode( $body ),
				'response' => array( 'code' => 200, 'message' => 'OK' ),
				'cookies'  => array(),
				'filename' => null,
			);
		},
		10,
		3
	);

	$gateway = new Y2KWEBS_BAC_Gateway();
	$authentication_reason_method = new ReflectionMethod( Y2KWEBS_BAC_Gateway::class, 'authentication_failure_reason' );
	$authentication_reason_method->setAccessible( true );
	powertranz_test_assert(
		'three_ds_not_completed' === $authentication_reason_method->invoke( $gateway, '', array( 'IsoResponseCode' => '12' ) ),
		'A provider ISO error without AuthenticationStatus must be classified as an incomplete 3DS flow.'
	);
	powertranz_test_assert(
		'authentication_status_not_allowed' === $authentication_reason_method->invoke( $gateway, 'N', array( 'IsoResponseCode' => '3D0' ) ),
		'AuthenticationStatus N must retain the explicit authentication policy reason.'
	);
	wp_set_current_user( 0 );
	powertranz_test_assert( ! $gateway->is_available(), 'Administrator-only mode must hide the gateway from guests.' );
	wp_set_current_user( 1 );
	powertranz_test_assert( $gateway->is_available(), 'Administrator-only mode must allow a WooCommerce administrator.' );
	$order   = wc_create_order();
	$order->set_currency( 'USD' );
	$order->set_total( '210.43' );
	$order->set_payment_method( $gateway );
	$order->set_billing_first_name( 'José Nombre Demasiado Largo Para PowerTranz' );
	$order->set_billing_last_name( 'Shopper Apellido Demasiado Largo Para PowerTranz' );
	$order->set_billing_address_1( 'Test address & suite with more than thirty characters' );
	$order->set_billing_address_2( 'Additional address information that is longer than fifty characters' );
	$order->set_billing_city( 'A city name longer than twenty five characters' );
	$order->set_billing_country( 'ES' );
	$order->set_billing_email( 'test@example.com' );
	$order->set_billing_phone( '+506 2222-3333' );
	$order->save();

	$_POST = array(
		'y2kwebs_bac_cardholder'  => 'Test Shopper',
		'y2kwebs_bac_card_pan'    => '4012 0000 0002 0006',
		'y2kwebs_bac_card_expiry' => '12/99',
		'y2kwebs_bac_card_cvv'    => '323',
	);

	$result = $gateway->process_payment( $order->get_id() );
	powertranz_test_assert( 'success' === $result['result'], 'A valid mocked Sale must start authentication.' );

	$order = wc_get_order( $order->get_id() );
	powertranz_test_assert( 'authentication_pending' === $order->get_meta( '_y2kwebs_bac_status', true ), 'Order must enter authentication_pending.' );
	powertranz_test_assert( '840' === $order->get_meta( '_y2kwebs_bac_currency_code', true ), 'USD order must persist only currency code 840.' );

	ob_start();
	$gateway->receipt_page( $order->get_id() );
	$receipt_html = ob_get_clean();
	powertranz_test_assert( false !== strpos( $receipt_html, 'Por favor, espere mientras procesamos su pago.' ), 'Receipt page must use a neutral waiting message.' );
	powertranz_test_assert( false === strpos( $receipt_html, 'Complete la autenticación solicitada por su banco.' ), 'Receipt page must not claim that the bank requested authentication.' );
	powertranz_test_assert( false !== strpos( $receipt_html, 'srcdoc=' ), 'PowerTranz RedirectData must be rendered with srcdoc as documented.' );
	powertranz_test_assert( false === strpos( $receipt_html, 'sandbox=' ), 'The 3DS iframe must not sandbox issuer challenge scripts, storage, cookies, or navigation.' );

	$stored_redirect_data = $order->get_meta( '_y2kwebs_bac_redirect_data', true );
	$entity_html = '<form><input name="Response" value="{&quot;result&quot;:&quot;ok&quot;}"></form>';
	$order->update_meta_data( '_y2kwebs_bac_redirect_data', $entity_html ); $order->save();
	ob_start(); $gateway->receipt_page( $order->get_id() ); $entity_receipt = ob_get_clean();
	$entity_doc = new DOMDocument(); @$entity_doc->loadHTML( $entity_receipt );
	powertranz_test_assert( $entity_html === $entity_doc->getElementsByTagName( 'iframe' )->item( 0 )->getAttribute( 'srcdoc' ), 'Nested bank HTML entities must survive the srcdoc attribute byte for byte.' );
	$order->delete_meta_data( '_y2kwebs_bac_redirect_data' );
	$order->save();
	ob_start();
	$gateway->receipt_page( $order->get_id() );
	$missing_receipt_html = ob_get_clean();
	powertranz_test_assert( false !== strpos( $missing_receipt_html, 'No pudimos cargar el proceso de pago.' ), 'Missing payment data must show a clear customer message.' );
	powertranz_test_assert( false === strpos( $missing_receipt_html, 'No existe una autenticación de PowerTranz pendiente' ), 'Missing payment data must not expose internal gateway state.' );
	$order->update_meta_data( '_y2kwebs_bac_redirect_data', $stored_redirect_data );
	$order->save();

	$stored_meta = wp_json_encode( $order->get_meta_data() );
	powertranz_test_assert( false === strpos( $stored_meta, '4012000000020006' ), 'PAN must never be persisted in order metadata.' );
	powertranz_test_assert( false === strpos( $stored_meta, '323' ), 'CVV must never be persisted in order metadata.' );

	$sale_request = $captured_requests[0];
	$sale_payload = json_decode( $sale_request['args']['body'], true );
	powertranz_test_assert( false !== strpos( $sale_request['args']['body'], '"TotalAmount":210.43' ), 'Sale must send TotalAmount as the exact fixed-decimal JSON number.' );
	powertranz_test_assert( false === strpos( $sale_request['args']['body'], '210.43000000000001' ), 'Sale must never expose a binary floating-point artifact.' );
	powertranz_test_assert( 'https://staging.ptranz.com/api/spi/sale' === $sale_request['url'], 'Sale must use the staging SPI endpoint.' );
	powertranz_test_assert( 0 === $sale_request['args']['redirection'], 'PowerTranz HTTP redirects must be disabled.' );
	powertranz_test_assert( isset( $sale_request['args']['headers']['PowerTranz-PowerTranzId'] ), 'Sale must include the merchant ID header.' );
	powertranz_test_assert( 'usd-test-id' === $sale_request['args']['headers']['PowerTranz-PowerTranzId'], 'USD Sale must use the USD merchant account.' );
	powertranz_test_assert( 30 === strlen( $sale_payload['BillingAddress']['FirstName'] ), 'Billing first name must respect the PowerTranz 30-character maximum.' );
	powertranz_test_assert( 30 === strlen( $sale_payload['BillingAddress']['LastName'] ), 'Billing last name must respect the PowerTranz 30-character maximum.' );
	powertranz_test_assert( 'Jose Nombre Demasiado Largo Pa' === $sale_payload['BillingAddress']['FirstName'], 'Text sent to FAC must be normalized before truncation.' );
	powertranz_test_assert( 'Test address suite with more t' === $sale_payload['BillingAddress']['Line1'], 'Billing address line 1 must be sanitized and limited to 30 characters.' );
	powertranz_test_assert( 50 === strlen( $sale_payload['BillingAddress']['Line2'] ), 'Billing address line 2 must respect the PowerTranz 50-character maximum.' );
	powertranz_test_assert( 25 === strlen( $sale_payload['BillingAddress']['City'] ), 'Billing city must respect the PowerTranz 25-character maximum.' );
	powertranz_test_assert( '724' === $sale_payload['BillingAddress']['CountryCode'], 'Spain must map to ISO 3166 numeric code 724.' );
	powertranz_test_assert( strlen( $sale_payload['Source']['CardholderName'] ) <= 45, 'Cardholder name must respect the PowerTranz 45-character maximum.' );
	powertranz_test_assert( ! isset( $sale_payload['BillingAddress']['State'] ), 'FAC prohibits sending State.' );
	powertranz_test_assert( ! isset( $sale_payload['BillingAddress']['PostalCode'] ), 'FAC prohibits sending PostalCode.' );
	powertranz_test_assert( '50622223333' === $sale_payload['BillingAddress']['PhoneNumber'], 'FAC requires a normalized phone number.' );
	powertranz_test_assert( 'test@example.com' === $sale_payload['BillingAddress']['EmailAddress'], 'FAC requires an email address.' );

	$client  = new Y2KWEBS_BAC_API_Client( 'https://staging.ptranz.com/api/spi' );
	$payment = $client->payment( 'spi-test-token' );
	powertranz_test_assert( ! is_wp_error( $payment ), 'Mocked payment completion must return decoded JSON.' );

	$payment_request = $captured_requests[1];
	powertranz_test_assert( '"spi-test-token"' === $payment_request['args']['body'], 'Payment body must be the quoted SPI token.' );
	powertranz_test_assert( ! isset( $payment_request['args']['headers']['PowerTranz-PowerTranzId'] ), 'Payment completion must not include merchant credentials.' );

	$failed_order = wc_create_order();
	$failed_order->set_currency( 'USD' );
	$failed_order->set_total( '336.70' );
	$failed_order->set_payment_method( $gateway );
	$failed_order->set_billing_first_name( 'Test' );
	$failed_order->set_billing_last_name( 'Failure' );
	$failed_order->set_billing_address_1( 'Test address' );
	$failed_order->set_billing_country( 'CR' );
	$failed_order->set_billing_email( 'failure@example.com' );
	$failed_order->set_billing_phone( '+506 2222-3333' );
	$failed_order->save();

	$_POST = array(
		'y2kwebs_bac_cardholder'  => 'Test Failure',
		'y2kwebs_bac_card_pan'    => '4111 1111 1111 1111',
		'y2kwebs_bac_card_expiry' => '12/99',
		'y2kwebs_bac_card_cvv'    => '123',
	);

	$failed_result = $gateway->process_payment( $failed_order->get_id() );
	powertranz_test_assert( 'failure' === $failed_result['result'], 'A non-SP4 Sale response must fail closed.' );
	$failed_order = wc_get_order( $failed_order->get_id() );
	powertranz_test_assert( 'start_failed' === $failed_order->get_meta( '_y2kwebs_bac_status', true ), 'Rejected Sale must enter start_failed.' );

	$failed_notes = wc_get_order_notes( array( 'order_id' => $failed_order->get_id() ) );
	$note_text    = implode( ' ', wp_list_pluck( $failed_notes, 'content' ) );
	powertranz_test_assert( false !== strpos( $note_text, 'unexpected_iso_response_code' ), 'Order note must include the exact validation reason.' );
	powertranz_test_assert( false !== strpos( $note_text, '3D0' ), 'Order note must include the sanitized ISO response code.' );
	powertranz_test_assert( false !== strpos( $note_text, 'ResponseCode PowerTranz: 326' ), 'Order note must include the sanitized PowerTranz response code.' );
	powertranz_test_assert( false !== strpos( $note_text, '57: Missing 3DS field: PhoneNumber' ), 'Order note must include the sanitized FAC error detail.' );
	powertranz_test_assert( false === strpos( $note_text, '4333333333332222' ), 'Order note must redact PAN-like digit sequences.' );
	powertranz_test_assert( false === strpos( $note_text, '123' ), 'Order note must not expose CVV-like digits from the provider message.' );
	powertranz_test_assert( false === strpos( $note_text, 'forbidden-spi-token' ), 'Order note must never include SpiToken.' );
	powertranz_test_assert( false === strpos( $note_text, 'forbidden-redirect-data' ), 'Order note must never include RedirectData.' );

	$failure_response = json_decode( $captured_requests[2]['args']['body'], true );
	powertranz_test_assert( false !== strpos( $captured_requests[2]['args']['body'], '"TotalAmount":336.70' ), 'Sale must preserve a trailing zero in TotalAmount.' );
	powertranz_test_assert( '4111111111111111' === $failure_response['Source']['CardPan'], 'Failure fixture must exercise the intended test card.' );
	$authentication_failure_response = array(
		'Approved'        => false,
		'IsoResponseCode' => '3D0',
		'ResponseCode'    => '326',
		'ResponseMessage' => '3DS authentication failed for PAN 4333333333332222',
		'RiskManagement'  => array(
			'ThreeDSecure' => array(
				'AuthenticationStatus'       => 'N',
				'ResponseCode'               => '3D0',
				'Eci'                        => '07',
				'ProtocolVersion'            => '2.2.0',
				'FingerprintIndicator'       => 'U',
				'AuthenticationStatusReason' => '01',
				'TransStatusReason'          => '02',
				'CardholderInfo'              => '<script>alert(1)</script> Contact test@example.com or 50622223333 token AbCdEfGhIjKlMnOpQrStUvWxYz123456',
				'Cavv'                       => 'forbidden-cavv',
				'Xid'                        => 'forbidden-xid',
				'DsTransId'                  => 'forbidden-ds-transaction-id',
			),
		),
		'SpiToken'        => 'forbidden-spi-token',
	);

	$diagnostic_context = Y2KWEBS_BAC_Diagnostics::failure_context(
		$failed_order,
		'authentication_status_not_allowed',
		array_merge(
			$authentication_failure_response,
			array(
				'Approved'        => false,
				'IsoResponseCode' => '3D0',
				'ResponseMessage' => 'PAN 4333333333332222 CVV 123',
				'Errors'          => array(
					array(
						'Code'    => '57',
						'Message' => 'Missing 3DS field: PhoneNumber',
					),
					array(
						'Code'    => '38',
						'Message' => 'Invalid PAN 4333333333332222 and token AbCdEfGhIjKlMnOpQrStUvWxYz123456',
					),
				),
				'SpiToken'        => 'forbidden-spi-token',
				'RedirectData'    => 'forbidden-redirect-data',
			)
		),
		array( 'id' => 'merchant-secret-12345678' ),
		'production'
	);
	$diagnostic_json = wp_json_encode( $diagnostic_context );
	powertranz_test_assert( false === strpos( $diagnostic_json, '4333333333332222' ), 'Diagnostic context must redact PAN-like values.' );
	powertranz_test_assert( false === strpos( $diagnostic_json, 'forbidden-spi-token' ), 'Diagnostic context must exclude SpiToken.' );
	powertranz_test_assert( false === strpos( $diagnostic_json, 'forbidden-redirect-data' ), 'Diagnostic context must exclude RedirectData.' );
	powertranz_test_assert( false === strpos( $diagnostic_json, 'merchant-secret-12345678' ), 'Diagnostic context must mask the merchant ID.' );
	powertranz_test_assert( false !== strpos( $diagnostic_context['provider_errors'], '57: Missing 3DS field: PhoneNumber' ), 'Diagnostic context must preserve safe FAC error details.' );
	powertranz_test_assert( false === strpos( $diagnostic_json, 'AbCdEfGhIjKlMnOpQrStUvWxYz123456' ), 'Diagnostic context must redact token-like values inside FAC errors.' );
	powertranz_test_assert( '***5678' === $diagnostic_context['masked_powertranz_id'], 'Diagnostic context must retain only the last four merchant ID characters.' );
	powertranz_test_assert( 'N' === $diagnostic_context['authentication_status'], 'Diagnostic context must include AuthenticationStatus.' );
	powertranz_test_assert( '326' === $diagnostic_context['gateway_response_code'], 'Diagnostic context must include the top-level PowerTranz ResponseCode.' );
	powertranz_test_assert( '3D0' === $diagnostic_context['three_ds_response_code'], 'Diagnostic context must include the 3DS ResponseCode.' );
	powertranz_test_assert( '07' === $diagnostic_context['eci'], 'Diagnostic context must include ECI.' );
	powertranz_test_assert( '2.2.0' === $diagnostic_context['protocol_version'], 'Diagnostic context must include the 3DS protocol version.' );
	powertranz_test_assert( 'U' === $diagnostic_context['fingerprint_indicator'], 'Diagnostic context must include FingerprintIndicator.' );
	powertranz_test_assert( '01' === $diagnostic_context['authentication_status_reason'], 'Diagnostic context must include AuthenticationStatusReason when provided.' );
	powertranz_test_assert( '02' === $diagnostic_context['transaction_status_reason'], 'Diagnostic context must include TransStatusReason when provided.' );
	powertranz_test_assert( false !== strpos( $diagnostic_context['cardholder_info'], '[email]' ), 'CardholderInfo must redact email addresses.' );
	powertranz_test_assert( false !== strpos( $diagnostic_context['cardholder_info'], '[number]' ), 'CardholderInfo must redact phone-like numbers.' );
	powertranz_test_assert( false === strpos( $diagnostic_json, 'forbidden-cavv' ), 'Diagnostic context must exclude CAVV.' );
	powertranz_test_assert( false === strpos( $diagnostic_json, 'forbidden-xid' ), 'Diagnostic context must exclude XID.' );
	powertranz_test_assert( false === strpos( $diagnostic_json, 'forbidden-ds-transaction-id' ), 'Diagnostic context must exclude DS Transaction ID.' );

	$authentication_failed_order = wc_create_order();
	$authentication_failed_order->set_currency( 'USD' );
	$authentication_failed_order->set_total( '315.66' );
	$authentication_failed_order->set_payment_method( $gateway );
	$authentication_failed_order->save();
	$failure_method = new ReflectionMethod( Y2KWEBS_BAC_Gateway::class, 'fail_authenticated_order' );
	$failure_method->setAccessible( true );
	$failure_method->invoke( $gateway, $authentication_failed_order, 'authentication_status_not_allowed', $authentication_failure_response );
	$authentication_failed_order = wc_get_order( $authentication_failed_order->get_id() );
	powertranz_test_assert( 'failed' === $authentication_failed_order->get_status(), 'AuthenticationStatus N must fail the order.' );
	powertranz_test_assert( 'N' === $authentication_failed_order->get_meta( '_y2kwebs_bac_authentication_status', true ), 'Failed order must persist AuthenticationStatus.' );
	powertranz_test_assert( '326' === $authentication_failed_order->get_meta( '_y2kwebs_bac_gateway_response_code', true ), 'Failed order must persist the PowerTranz ResponseCode.' );
	powertranz_test_assert( '3D0' === $authentication_failed_order->get_meta( '_y2kwebs_bac_three_ds_response_code', true ), 'Failed order must persist the 3DS ResponseCode.' );
	powertranz_test_assert( '07' === $authentication_failed_order->get_meta( '_y2kwebs_bac_eci', true ), 'Failed order must persist ECI.' );
	powertranz_test_assert( '2.2.0' === $authentication_failed_order->get_meta( '_y2kwebs_bac_protocol_version', true ), 'Failed order must persist ProtocolVersion.' );
	$authentication_notes = wc_get_order_notes( array( 'order_id' => $authentication_failed_order->get_id() ) );
	$authentication_note_text = implode( ' ', wp_list_pluck( $authentication_notes, 'content' ) );
	powertranz_test_assert( false !== strpos( $authentication_note_text, 'AuthenticationStatus: N' ), 'Authentication failure note must include AuthenticationStatus.' );
	powertranz_test_assert( false !== strpos( $authentication_note_text, 'ResponseCode 3DS: 3D0' ), 'Authentication failure note must include the 3DS ResponseCode.' );
	powertranz_test_assert( false !== strpos( $authentication_note_text, 'ResponseCode PowerTranz: 326' ), 'Authentication failure note must include the PowerTranz ResponseCode.' );
	powertranz_test_assert( false !== strpos( $authentication_note_text, 'ECI: 07' ), 'Authentication failure note must include ECI.' );
	powertranz_test_assert( false !== strpos( $authentication_note_text, 'protocolo: 2.2.0' ), 'Authentication failure note must include ProtocolVersion.' );
	powertranz_test_assert( false === strpos( $authentication_note_text, 'test@example.com' ), 'Authentication failure note must redact email addresses.' );
	powertranz_test_assert( false === strpos( $authentication_note_text, '50622223333' ), 'Authentication failure note must redact phone-like numbers.' );
	powertranz_test_assert( false === strpos( $authentication_note_text, 'forbidden-cavv' ), 'Authentication failure note must exclude CAVV.' );
	powertranz_test_assert( false === strpos( $authentication_note_text, 'forbidden-spi-token' ), 'Authentication failure note must exclude SpiToken.' );

	echo "POWERTRANZ_INTEGRATION_SMOKE_OK\n";
} finally {
	remove_all_filters( 'pre_http_request' );
	if ( false !== $previous_precision ) {
		ini_set( 'serialize_precision', $previous_precision );
	}
	$_POST = $previous_post;
	wp_set_current_user( $previous_user_id );
	if ( $order instanceof WC_Order ) {
		$order->delete( true );
	}
	if ( $failed_order instanceof WC_Order ) {
		$failed_order->delete( true );
	}
	if ( $authentication_failed_order instanceof WC_Order ) {
		$authentication_failed_order->delete( true );
	}
	if ( null === $previous_settings ) {
		delete_option( $option_name );
	} else {
		update_option( $option_name, $previous_settings, false );
	}
	if ( null === $previous_secrets ) { delete_option( Y2KWEBS_BAC_Secrets::OPTION ); } else { update_option( Y2KWEBS_BAC_Secrets::OPTION, $previous_secrets, false ); }
	wc_clear_notices();
}

// Keep the credential checks in the integration suite used by both CI matrices.
require dirname( __DIR__ ) . '/settings.php';
