<?php

defined( 'ABSPATH' ) || exit;

/**
 * Builds allowlisted, redacted diagnostics for failed PowerTranz requests.
 */
final class Y2KWEBS_BAC_Diagnostics {
	const LOG_SOURCE = 'y2kwebs-bac';

	/**
	 * Returns an empty string when the initial SPI response is valid.
	 *
	 * @param array<string, mixed> $response PowerTranz response.
	 * @param array<string, mixed> $expected Expected transaction values.
	 */
	public static function initial_response_error( array $response, array $expected ) {
		if ( ! isset( $response['IsoResponseCode'] ) || ! is_scalar( $response['IsoResponseCode'] ) ) {
			return 'missing_iso_response_code';
		}

		if ( 'SP4' !== strtoupper( trim( (string) $response['IsoResponseCode'] ) ) ) {
			return 'unexpected_iso_response_code';
		}

		if ( ! isset( $response['RedirectData'] ) || ! is_scalar( $response['RedirectData'] ) || '' === trim( (string) $response['RedirectData'] ) ) {
			return 'missing_redirect_data';
		}

		$field_map = array(
			'TransactionIdentifier' => 'transaction_id',
			'OrderIdentifier'       => 'order_reference',
			'CurrencyCode'          => 'currency_code',
		);

		foreach ( $field_map as $response_field => $expected_field ) {
			if ( ! isset( $response[ $response_field ] ) || ! is_scalar( $response[ $response_field ] ) ) {
				return 'missing_' . sanitize_key( $expected_field );
			}

			if ( ! isset( $expected[ $expected_field ] ) || ! hash_equals( (string) $expected[ $expected_field ], (string) $response[ $response_field ] ) ) {
				return 'mismatched_' . sanitize_key( $expected_field );
			}
		}

		if ( ! isset( $response['TotalAmount'] ) || ! is_scalar( $response['TotalAmount'] ) ) {
			return 'missing_total_amount';
		}

		$expected_amount = wc_format_decimal( isset( $expected['total_amount'] ) ? $expected['total_amount'] : '', 2 );
		$actual_amount   = wc_format_decimal( $response['TotalAmount'], 2 );
		if ( ! hash_equals( (string) $expected_amount, (string) $actual_amount ) ) {
			return 'mismatched_total_amount';
		}

		return '';
	}

	/**
	 * Creates a context containing only explicitly allowed diagnostic fields.
	 *
	 * @param WC_Order             $order Order being processed.
	 * @param string               $reason Internal validation reason.
	 * @param array<string, mixed> $response PowerTranz response.
	 * @param array<string, mixed> $account Selected merchant account.
	 * @param string               $environment Gateway environment.
	 * @return array<string, int|string>
	 */
	public static function failure_context( WC_Order $order, $reason, array $response, array $account, $environment ) {
		$approved = 'unknown';
		if ( isset( $response['Approved'] ) && is_scalar( $response['Approved'] ) ) {
			if ( true === $response['Approved'] || 1 === $response['Approved'] || 'true' === strtolower( (string) $response['Approved'] ) ) {
				$approved = 'yes';
			} elseif ( false === $response['Approved'] || 0 === $response['Approved'] || 'false' === strtolower( (string) $response['Approved'] ) ) {
				$approved = 'no';
			}
		}

		$environment = in_array( $environment, array( 'staging', 'production' ), true ) ? $environment : 'unknown';
		$three_ds    = isset( $response['RiskManagement']['ThreeDSecure'] ) && is_array( $response['RiskManagement']['ThreeDSecure'] )
			? $response['RiskManagement']['ThreeDSecure']
			: array();

		return array(
			'order_id'                     => $order->get_id(),
			'environment'                  => $environment,
			'currency'                     => self::safe_identifier( $order->get_currency(), 3 ),
			'amount'                       => wc_format_decimal( $order->get_total(), 2 ),
			'reason'                       => self::safe_identifier( $reason, 64 ),
			'iso_response_code'            => self::safe_identifier( isset( $response['IsoResponseCode'] ) ? $response['IsoResponseCode'] : '', 16 ),
			'gateway_response_code'        => self::safe_identifier( isset( $response['ResponseCode'] ) ? $response['ResponseCode'] : '', 16 ),
			'response_message'             => self::safe_message( isset( $response['ResponseMessage'] ) ? $response['ResponseMessage'] : '' ),
			'provider_errors'              => self::safe_provider_errors( $response ),
			'approved'                     => $approved,
			'authentication_status'        => self::safe_identifier( isset( $three_ds['AuthenticationStatus'] ) ? $three_ds['AuthenticationStatus'] : '', 8 ),
			'three_ds_response_code'       => self::safe_identifier( isset( $three_ds['ResponseCode'] ) ? $three_ds['ResponseCode'] : '', 16 ),
			'eci'                          => self::safe_identifier( isset( $three_ds['Eci'] ) ? $three_ds['Eci'] : '', 8 ),
			'protocol_version'             => self::safe_identifier( isset( $three_ds['ProtocolVersion'] ) ? $three_ds['ProtocolVersion'] : '', 16 ),
			'fingerprint_indicator'        => self::safe_identifier( isset( $three_ds['FingerprintIndicator'] ) ? $three_ds['FingerprintIndicator'] : '', 8 ),
			'authentication_status_reason' => self::safe_identifier( isset( $three_ds['AuthenticationStatusReason'] ) ? $three_ds['AuthenticationStatusReason'] : '', 16 ),
			'transaction_status_reason'    => self::safe_identifier( isset( $three_ds['TransStatusReason'] ) ? $three_ds['TransStatusReason'] : '', 16 ),
			'cardholder_info'              => self::safe_message( isset( $three_ds['CardholderInfo'] ) ? $three_ds['CardholderInfo'] : '' ),
			'transaction_id'               => self::safe_identifier( $order->get_meta( Y2KWEBS_BAC_Gateway::META_PREFIX . 'transaction_id', true ), 64 ),
			'masked_powertranz_id'         => self::mask_identifier( isset( $account['id'] ) ? $account['id'] : '' ),
		);
	}

	/**
	 * Produces a safe order note for administrators.
	 *
	 * @param array<string, int|string> $context Safe diagnostic context.
	 */
	public static function order_note( array $context ) {
		$note = sprintf(
			/* translators: 1: internal reason, 2: ISO code, 3: PowerTranz response code, 4: provider message. */
			__( 'PowerTranz rechazó el inicio del pago. Diagnóstico: %1$s; ISO: %2$s; ResponseCode PowerTranz: %3$s; mensaje: %4$s.', 'y2kwebs-bac' ),
			$context['reason'] ?: '-',
			$context['iso_response_code'] ?: '-',
			$context['gateway_response_code'] ?: '-',
			$context['response_message'] ?: '-'
		);

		if ( ! empty( $context['provider_errors'] ) ) {
			$note .= ' ' . sprintf(
				/* translators: %s: sanitized PowerTranz error codes and messages. */
				__( 'Detalle FAC: %s.', 'y2kwebs-bac' ),
				$context['provider_errors']
			);
		}

		return $note;
	}

	/**
	 * Produces a safe note for a failed 3DS callback or payment completion.
	 *
	 * @param array<string, int|string> $context Safe diagnostic context.
	 */
	public static function authentication_order_note( array $context ) {
		$note = sprintf(
			/* translators: 1: reason, 2: authentication status, 3: 3DS response code, 4: ECI, 5: protocol, 6: ISO, 7: PowerTranz response code, 8: response message. */
			__( 'PowerTranz no completó el flujo de pago. Diagnóstico: %1$s; AuthenticationStatus: %2$s; ResponseCode 3DS: %3$s; ECI: %4$s; protocolo: %5$s; ISO: %6$s; ResponseCode PowerTranz: %7$s; mensaje: %8$s.', 'y2kwebs-bac' ),
			$context['reason'] ?: '-',
			$context['authentication_status'] ?: '-',
			$context['three_ds_response_code'] ?: '-',
			$context['eci'] ?: '-',
			$context['protocol_version'] ?: '-',
			$context['iso_response_code'] ?: '-',
			$context['gateway_response_code'] ?: '-',
			$context['response_message'] ?: '-'
		);

		$optional = array();
		if ( ! empty( $context['fingerprint_indicator'] ) ) {
			$optional[] = 'FingerprintIndicator: ' . $context['fingerprint_indicator'];
		}
		if ( ! empty( $context['authentication_status_reason'] ) ) {
			$optional[] = 'AuthenticationStatusReason: ' . $context['authentication_status_reason'];
		}
		if ( ! empty( $context['transaction_status_reason'] ) ) {
			$optional[] = 'TransStatusReason: ' . $context['transaction_status_reason'];
		}
		if ( ! empty( $context['cardholder_info'] ) ) {
			$optional[] = __( 'Información del emisor: ', 'y2kwebs-bac' ) . $context['cardholder_info'];
		}

		return $optional ? $note . ' ' . implode( '; ', $optional ) . '.' : $note;
	}

	/**
	 * Writes an allowlisted context to the WooCommerce log.
	 *
	 * @param array<string, int|string> $context Safe diagnostic context.
	 */
	public static function write_log( array $context ) {
		if ( ! function_exists( 'wc_get_logger' ) ) {
			return;
		}

		wc_get_logger()->error(
			'PowerTranz initial sale failed.',
			array_merge( array( 'source' => self::LOG_SOURCE ), $context )
		);
	}

	/**
	 * Writes a safe callback/payment failure context to the WooCommerce log.
	 *
	 * @param array<string, int|string> $context Safe diagnostic context.
	 */
	public static function write_authentication_log( array $context ) {
		if ( ! function_exists( 'wc_get_logger' ) ) {
			return;
		}

		wc_get_logger()->error(
			'PowerTranz 3DS authentication or payment completion failed.',
			array_merge( array( 'source' => self::LOG_SOURCE ), $context )
		);
	}

	private static function safe_identifier( $value, $maximum_length ) {
		$value = is_scalar( $value ) ? (string) $value : '';
		$value = preg_replace( '/[^A-Za-z0-9_.-]/', '', $value );
		return substr( (string) $value, 0, $maximum_length );
	}

	private static function safe_message( $value ) {
		$value = is_scalar( $value ) ? (string) $value : '';
		$value = sanitize_text_field( wp_strip_all_tags( $value ) );
		$value = preg_replace( '/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', '[email]', $value );
		$value = preg_replace( '/(?<![A-Za-z0-9+\/_=-])[A-Za-z0-9+\/_=-]{24,}(?![A-Za-z0-9+\/_=-])/', '[redacted]', $value );
		$value = preg_replace( '/\d{3,}/', '[number]', (string) $value );
		return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, 160 ) : substr( $value, 0, 160 );
	}

	/**
	 * Extracts only FAC error codes and messages, never the complete response.
	 *
	 * @param array<string, mixed> $response PowerTranz response.
	 */
	private static function safe_provider_errors( array $response ) {
		$errors = isset( $response['Errors'] ) ? $response['Errors'] : ( isset( $response['errors'] ) ? $response['errors'] : array() );
		if ( ! is_array( $errors ) ) {
			return '';
		}

		if ( isset( $errors['Code'] ) || isset( $errors['code'] ) || isset( $errors['Message'] ) || isset( $errors['message'] ) ) {
			$errors = array( $errors );
		}

		$safe_errors = array();
		foreach ( $errors as $error ) {
			if ( is_object( $error ) ) {
				$error = get_object_vars( $error );
			}

			if ( ! is_array( $error ) ) {
				continue;
			}

			$code    = self::safe_identifier( isset( $error['Code'] ) ? $error['Code'] : ( isset( $error['code'] ) ? $error['code'] : '' ), 16 );
			$message = self::safe_message( isset( $error['Message'] ) ? $error['Message'] : ( isset( $error['message'] ) ? $error['message'] : '' ) );

			if ( '' === $code && '' === $message ) {
				continue;
			}

			$safe_errors[] = '' !== $code && '' !== $message ? $code . ': ' . $message : $code . $message;
			if ( 5 === count( $safe_errors ) ) {
				break;
			}
		}

		$value = implode( ' | ', $safe_errors );
		return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, 600 ) : substr( $value, 0, 600 );
	}

	private static function mask_identifier( $value ) {
		$value = self::safe_identifier( $value, 128 );
		return strlen( $value ) > 4 ? '***' . substr( $value, -4 ) : '***';
	}
}
