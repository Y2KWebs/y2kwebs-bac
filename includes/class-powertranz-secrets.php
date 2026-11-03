<?php

defined( 'ABSPATH' ) || exit;

/** Authenticated encryption for the administrator's merchant passwords. */
final class Y2KWEBS_BAC_Secrets {
	const OPTION = 'y2kwebs_bac_secrets';

	public static function seal( array $values ) {
		if ( ! function_exists( 'openssl_encrypt' ) ) {
			throw new RuntimeException( 'Credential encryption unavailable.' );
		}
		$json = wp_json_encode( $values );
		if ( false === $json ) {
			throw new RuntimeException( 'Invalid credential data.' );
		}
		$iv = random_bytes( 12 );
		$tag = '';
		$encrypted = openssl_encrypt( $json, 'aes-256-gcm', hash( 'sha256', wp_salt( 'auth' ), true ), OPENSSL_RAW_DATA, $iv, $tag, 'y2kwebs-bac-v1' );
		if ( false === $encrypted ) {
			throw new RuntimeException( 'Credential encryption unavailable.' );
		}
		return 'v1:' . base64_encode( $iv . $tag . $encrypted );
	}

	public static function open( $value ) {
		if ( ! is_string( $value ) || 0 !== strpos( $value, 'v1:' ) || ! function_exists( 'openssl_decrypt' ) ) {
			throw new RuntimeException( 'Protected credentials unavailable.' );
		}
		$raw = base64_decode( substr( $value, 3 ), true );
		if ( false === $raw || strlen( $raw ) < 29 ) {
			throw new RuntimeException( 'Invalid protected credentials.' );
		}
		$json = openssl_decrypt( substr( $raw, 28 ), 'aes-256-gcm', hash( 'sha256', wp_salt( 'auth' ), true ), OPENSSL_RAW_DATA, substr( $raw, 0, 12 ), substr( $raw, 12, 16 ), 'y2kwebs-bac-v1' );
		$values = false !== $json ? json_decode( $json, true, 8 ) : null;
		if ( ! is_array( $values ) ) {
			throw new RuntimeException( 'Protected credentials unavailable.' );
		}
		foreach ( $values as $key => $secret ) {
			if ( ! in_array( $key, array( 'crc_password', 'usd_password' ), true ) || ! self::valid( $secret ) ) {
				throw new RuntimeException( 'Invalid protected credentials.' );
			}
		}
		return $values;
	}

	public static function read() {
		$stored = get_option( self::OPTION, '' );
		return '' === $stored ? array() : self::open( $stored );
	}

	public static function get( $key ) {
		try {
			$values = self::read();
			return isset( $values[ $key ] ) ? $values[ $key ] : '';
		} catch ( Throwable $error ) {
			return '';
		}
	}

	public static function valid( $value ) {
		return is_string( $value ) && strlen( $value ) <= 4096 && ! preg_match( '/[\x00-\x1f\x7f]/', $value );
	}
}
